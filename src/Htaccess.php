<?php 
namespace HtaccessManager;
class Htaccess {
    private $path = "";
    private $serverType = "";
    private $serverSoftware = "";
    private $ipDenyCode = 1;
    private $ipDenyMsg = [
        3=>"NGINX detected – use nginx.conf, not .htaccess",
        4=>"Unknown or unsupported web server – manual configuration required"
    ];
    // Check for Apache 2.2 rules
    private $apache22Rules = [
        'deny from',
        'order allow,deny',
        'order deny,allow',
        'allow from'
    ];

    // Check for Apache 2.4+ rules
    private $apache24Rules = [
        'require all denied',
        'require all granted',
        'require ip',
        'require not ip'
    ];    
    public function __construct()
    {
        //parent::__construct();
        $this->path = $_SERVER['DOCUMENT_ROOT'] . '/.htaccess';
        $this->serverSoftware = isset($_SERVER['SERVER_SOFTWARE']) ? strtolower($_SERVER['SERVER_SOFTWARE']):'';
        $this->ipDenyCode = $this->detectApacheMajorVersion();
    }
    function getAllowed()
    {
        if (!function_exists('file_get_contents')) return "file_get_contents is not exist.";
        $disable_functions = ini_get('disable_functions');
        $disable_functions = !empty($disable_functions) ? explode(',',$disable_functions):[];
        if(!empty($disable_functions))
        {
            if (in_array('file_get_contents',$disable_functions)) return  "file_get_contents is disabled in php.ini.";
        }
        return "OK";
    }
    function putAllowed(){
        if (!function_exists('file_put_contents')) return "file_put_contents is not exist.";
        $disable_functions = ini_get('disable_functions');
        $disable_functions = !empty($disable_functions) ? explode(',',$disable_functions):[];
        if(!empty($disable_functions))
        {
            if (in_array('file_put_contents',$disable_functions)) return  "file_put_contents is disabled in php.ini.";
        }
        return "OK";
    }
    function get($filePath)
    {
        try {
            if (!file_exists($filePath))
            {
                return ["status"=>"error","message"=>"File does not exist at $filePath."];
            }
            $contents = file_get_contents($filePath);
            if ($contents === false)
            {
                return ["status"=>"error","message"=>"Unable to read file. Check permissions or file integrity."];
            }
            return ["status"=>"success","data"=>$contents];
        } catch (Exception $e) {
            return ["status"=>"error","message"=>"Error: " . $e->getMessage()];
        }
    }
    function put($filePath="",$content="")
    {
        try {
            if (!file_exists($filePath))
            {
                return ["status"=>"error","message"=>"File does not exist at $filePath."];
            }
            $isValid = $this->validate($content);
            if($isValid["status"] == "error") return $isValid;

            $contents = file_put_contents($filePath,$content);
            if ($contents === false)
            {
                return ["status"=>"error","message"=>"Unable to write file. Check permissions or file integrity."];
            }
            return ["status"=>"success","message"=>"File updated successfully"];
        } catch (Exception $e)
        {
            return ["status"=>"error","message"=>"Error: " . $e->getMessage()];
        }
    }
    function parse($content)
    {
        $lines = preg_split('/\R/', $content);
        $stack = [];
        $root = ['type' => 'root', 'rules' => '', 'data' => []];

        $currentPath = [&$root];  // array of references to path stack

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) continue;

            // Opening tag
            if (preg_match('/^<(\w+)([^>]*)>$/i', $line, $openMatch)) {
                $tag = strtolower($openMatch[1]);
                $rules = trim($openMatch[2]);

                $newBlock = [
                    'type' => $tag,
                    'rules' => $rules,
                    'data' => []
                ];

                $parent = &$currentPath[count($currentPath) - 1];
                $parent['data'][] = $newBlock;

                // Append a REFERENCE to newly added block
                $index = count($parent['data']) - 1;
                $currentPath[] = &$parent['data'][$index];
            }

            // Closing tag
            elseif (preg_match('/^<\/(\w+)>$/i', $line)) {
                array_pop($currentPath);
            }

            // Regular line
            else {
                $current = &$currentPath[count($currentPath) - 1];
                $current['data'][] = $line;
            }
        }

        return $root;
    }
    function mapType($type) {
        $map = [
            'ifmodule'     => 'IfModule',
            'filesmatch'   => 'FilesMatch',
            'files'        => 'Files',
            'directory'    => 'Directory',
            'directorymatch' => 'DirectoryMatch',
            'location'     => 'Location',
            'locationmatch'=> 'LocationMatch',
            'limit'        => 'Limit',
            'limitexcept'  => 'LimitExcept',
            'proxy'        => 'Proxy',
            'proxymatch'   => 'ProxyMatch',
            'requireall'   => 'RequireAll',
            'requireany'   => 'RequireAny',
            'authnprovideralias' => 'AuthnProviderAlias',
            'if'           => 'If',
            'else'         => 'Else',
            'elseif'       => 'ElseIf',
        ];
        return $map[strtolower($type)] ?? $type;
    }
    function removeLine(array &$parsed, string $targetType, string $targetLine): void {
        if (!isset($parsed['data']) || !is_array($parsed['data'])) return;

        foreach ($parsed['data'] as $index => &$block) {
            if (
                is_array($block) &&
                isset($block['type'], $block['data']) &&
                strtolower($block['type']) === strtolower($targetType)
            ) {
                // Remove the matching line from this block
                $block['data'] = array_filter($block['data'], function ($line) use ($targetLine) {
                    return trim($line) !== trim($targetLine);
                });

                // If after filtering, data is empty, remove the block entirely
                if (empty($block['data'])) {
                    unset($parsed['data'][$index]);
                }
            }
        }
        // Re-index the array to clean up gaps
        $parsed['data'] = array_values($parsed['data']);
    }

    function remove($type,$newRules)
    {
        $info = $this->get($this->path);
        if($info["status"] == "error") return $info;
        $parsedRules = $this->parse($info["data"]);
        if(is_array($newRules))
        {
            foreach($newRules as $rule){
                $this->removeLine($parsedRules,$type,$rule);                
            }
        }else{
            $this->removeLine($parsedRules,$type,$rule);            
        }
        $newHtaccess .= $this->rebuild($parsedRules,2);
        return $this->put($this->path, trim($newHtaccess) . "\n");
    }
    function merge(array &$parsed, array $required) {
        foreach ($required['data'] as $newItem) {
            $isDuplicate = false;

            // Case 1: Simple string line (like comments or directives)
            if (is_string($newItem)) {
                if (!in_array($newItem, $parsed['data'], true)) {
                    $parsed['data'][] = $newItem;
                }
                continue;
            }

            // Case 2: Structured block (with 'type' and 'rules')
            if (is_array($newItem) && isset($newItem['type'])) {
                foreach ($parsed['data'] as &$existingItem) {
                    if (!is_array($existingItem) || !isset($existingItem['type'])) {
                        continue;
                    }

                    // Check if same type and rules
                    if (
                        strtolower($existingItem['type']) === strtolower($newItem['type']) &&
                        trim(strtolower($existingItem['rules'] ?? '')) === trim(strtolower($newItem['rules'] ?? ''))
                    ){
                        // Recurse to merge nested data
                        if (isset($newItem['data']) && is_array($newItem['data']))
                        {
                            if (!isset($existingItem['data']) || !is_array($existingItem['data']))
                            {
                                $existingItem['data'] = [];
                            }
                            // Wrap for recursive merge
                            $existingWrap = ['type' => 'root', 'data' => &$existingItem['data']];
                            $newWrap = ['type' => 'root', 'data' => $newItem['data']];
                            $this->merge($existingWrap, $newWrap);
                        }
                        $isDuplicate = true;
                        break;
                    }
                }
                // If no matching structured block found, append new one
                if (!$isDuplicate) {
                    $parsed['data'][] = $newItem;
                }
            }
        }
    }    
    function getWebServerType() {
        if (!isset($this->serverSoftware)) return 'unknown';
        if (strpos($this->serverSoftware, 'apache') !== false) return 'apache';
        if (strpos($this->serverSoftware, 'litespeed') !== false) return 'litespeed';
        if (strpos($this->serverSoftware, 'nginx') !== false) return 'nginx';
        return 'other';
    }
    function getApacheVersion()
    {
        if (function_exists('apache_get_version'))
        {
            $version = apache_get_version();
            preg_match('/Apache\/(\d+\.\d+)/i', $version, $matches);
            return $matches[1] ?? null;
        }
        if (isset($this->serverSoftware))
        {
            preg_match('/Apache\/(\d+\.\d+)/i', $this->serverSoftware, $matches);
            return $matches[1] ?? null;
        }
        return null;
    }    
    function detectApacheMajorVersion()
    {
        $type = $this->getWebServerType();
        if(in_array($type,['apache','litespeed'])){
            $version = $this->getApacheVersion();
            // Apache 2.4 and above
            if ($version && version_compare($version, '2.4', '>=')) return 1;
            // Apache 2.2 or unknown Apache version
            return 2;
        }
        if($serverType === 'nginx') return 3;
        return 4;
    }
    function rebuild($parsed, $depth = 0)
    {
        $indent = str_repeat("  ", $depth); // 4-space indentation
        $output = '';
        // If it's a root node
        if (isset($parsed['type']) && $parsed['type'] === 'root') {
            foreach ($parsed['data'] as $item) {
                $output .= $this->rebuild($item, $depth);
            }
        }
        // If it's a string (direct rule)
        elseif (is_string($parsed))
        {
            $output .= $indent . $parsed . "\n";
        }

        // If it's a directive block like <IfModule>, <Files>, etc.
        elseif (is_array($parsed) && isset($parsed['type'], $parsed['rules'], $parsed['data'])) {
            $rules = isset($parsed['rules']) ? $parsed['rules']:"";
            $mapType = $this->mapType($parsed['type']);
            if(!empty($rules))
            {
            $start = $indent . "<" . $mapType . " " . $parsed['rules'] . ">\n";
            }else{
            $start = $indent . "<" . $mapType . ">\n";
            }
            $end   = $indent . "</" . $mapType . ">\n";

            $middle = '';
            foreach ($parsed['data'] as $item)
            {
                $middle .= $this->rebuild($item, $depth + 1);
            }

            $output .= $start . $middle . $end;
        }

        return $output;
    }
    function validate($content)
    {
        // Normalize line endings
        $content = strtolower(str_replace(["\r\n", "\r"], "\n", $content));
        $content = $this->apacheRulesHandling($content);
        if ($this->ipDenyCode == 1)
        {
            // Apache 2.4+: should not use 2.2 rules
            $pattern = '/' . implode('|', array_map('preg_quote', $this->apache22Rules)) . '/i';
            if (preg_match($pattern, $content))
            {
                return ["status"=>"error","message"=>"Error: Apache 2.4+ mode does not support legacy Apache 2.2 rules."];
            }
        }
        if ($this->ipDenyCode == 2)
        {
            // Apache 2.2: should not use 2.4+ rules
            $pattern = '/' . implode('|', array_map('preg_quote', $this->apache24Rules)) . '/i';
            if (preg_match($pattern, $content))
            {
                return ["status"=>"error","message"=>"Error: Apache 2.2 mode does not support modern rule"];
            }
        }
        if (in_array($this->ipDenyCode,[3,4]))
        {
            //NGINX or unknown servers not supported check
            $InvalidRules = array_merge($this->apache24Rules,$this->apache22Rules);
            $pattern = '/' . implode('|', array_map('preg_quote', $InvalidRules)) . '/i';
            if (preg_match($pattern, $content))
            {
                $msg = $this->ipDenyMsg[$this->ipDenyCode];
                return ["status"=>"error","message"=>"Error: ".$msg];
            }
        }
        return ["status"=>"success"];
    }
    function convertApache24to22($content) {
        // Convert basic rules outside of blocks
        $content = preg_replace_callback('/^\s*Require\s+(all\s+(denied|granted))\s*$/mi', function ($m) {
            if (strtolower($m[1]) === 'all denied') {
                return "Order allow,deny\nDeny from all";
            } elseif (strtolower($m[1]) === 'all granted') {
                return "Order allow,deny\nAllow from all";
            }
            return $m[0];
        }, $content);

        // Convert "Require ip ..." and "Require not ip ..."
        $content = preg_replace_callback('/^\s*Require\s+(not\s+)?ip\s+([^\s]+)\s*$/mi', function ($m) {
            $not = isset($m[1]) && strtolower(trim($m[1])) === 'not';
            $ip = trim($m[2]);

            if ($not) {
                return "Order allow,deny\nDeny from $ip";
            } else {
                return "Order deny,allow\nAllow from $ip";
            }
        }, $content);

        // Handle blocks like <FilesMatch>, <Directory>, etc.
        $content = preg_replace_callback('/<(FilesMatch|Directory|Location)[^>]*>.*?<\/\1>/si', function ($block) {
            return preg_replace_callback('/^\s*Require\s+(.*?)\s*$/mi', function ($m) {
                $parts = explode(' ', $m[1]);
                if ($parts[0] === 'all') {
                    return strtolower($parts[1]) === 'granted'
                        ? "Order allow,deny\nAllow from all"
                        : "Order allow,deny\nDeny from all";
                } elseif ($parts[0] === 'ip') {
                    return "Order deny,allow\nAllow from " . $parts[1];
                } elseif ($parts[0] === 'not' && $parts[1] === 'ip') {
                    return "Order allow,deny\nDeny from " . $parts[2];
                }
                return $m[0]; // leave unchanged if unknown
            }, $block[0]);
        }, $content);

        return trim($content);
    }
    function convertApache22to24($content) {
        // Define tags to process
        $tags = ['Directory', 'Location', 'Files', 'FilesMatch'];
        foreach ($tags as $tag) {
            $content = preg_replace_callback(
                '#<' . $tag . '\s+[^>]+>(.*?)</' . $tag . '>#is',
                function ($matches) use ($tag) {
                    $fullBlock = $matches[0];
                    $inner     = $matches[1];

                    $granted = preg_match('/Allow from all/i', $inner);
                    $denied  = preg_match('/Deny from all/i', $inner);

                    // Default replacement logic
                    if ($granted && !$denied) {
                        $replacement = "  Require all granted";
                    } elseif ($denied && !$granted) {
                        $replacement = "  Require all denied";
                    } elseif ($granted && $denied) {
                        $replacement = "  # Mixed allow/deny logic – please verify manually";
                    } else {
                        $replacement = "  # Unknown access logic – please verify manually";
                    }

                    // Preserve tag line
                    if (preg_match('#<(' . $tag . '\s+[^>]+)>#i', $fullBlock, $openTag)) {
                        return "{$openTag[0]}\n{$replacement}\n</$tag>";
                    } else {
                        return $fullBlock;
                    }
                },
                $content
            );
        }

        // Optionally remove legacy directives that may be outside blocks
        $content = preg_replace('/^\s*(Order|Allow from|Deny from|Satisfy).*$/mi', '', $content);
        $content = preg_replace('/\n\s*\n/', "\n", $content); // clean extra empty lines

        return $content;
    }
    function apacheRulesHandling($content)
    {
        if ($this->ipDenyCode == 1)
        {
            $content = $this->convertApache22to24($content);
        }
        if ($this->ipDenyCode == 2)
        {
            $content = $this->convertApache24to22($content);
        }
        $content = preg_replace('/^\h*\v+/m', '', $content); // Remove empty lines
        $content = trim($content); // Final cleanup
        return $content;
    }
    function generate($required=[],$path="")
    {
        if(empty($path)) $path = $this->path;
        if(!file_exists($path)) return ["status"=>"error","message"=>".htaccess file not exist"];
        $isAllowed = $this->getAllowed();
        if($isAllowed != "OK") return ["status"=>"error","message"=>$isAllowed];
        $info = $this->get($path);
        if($info["status"] == "error") return $info;
        $isAllowed = $this->putAllowed();
        if($isAllowed != "OK") return ["status"=>"error","message"=>$isAllowed];
        $parsedRules = $this->parse($info["data"]);
        $this->merge($parsedRules,$required);
        $newHtaccess = "# Auto-generated .htaccess rules\n";
        $newHtaccess .= $this->rebuild($parsedRules,2);
        return ["status"=>"success","data"=>$newHtaccess];
    }
    function update($required=[])
    {
        $isGenerated = $this->generate($required);
        if($isGenerated["status"] == "error") return $isGenerated;
        $content = trim($isGenerated["data"]) . "\n";
        $isValid = $this->validate($content);
        if($isValid["status"] == "error") return $isValid;
        return $this->put($this->path,$content);
    }    
}
