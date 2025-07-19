<?php 
namespace HtaccessManager;
class Htaccess {
    private $path = "";
    public function __construct()
    {
        //parent::__construct();
        $this->path = $_SERVER['DOCUMENT_ROOT'] . '/.htaccess';
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
    function put($filePath="",$value="")
    {
        try {
            if (!file_exists($filePath))
            {
                return ["status"=>"error","message"=>"File does not exist at $filePath."];
            }
            $contents = file_put_contents($filePath,$value);
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
                        $existingItem['type'] === $newItem['type'] &&
                        ($existingItem['rules'] ?? null) === ($newItem['rules'] ?? null)
                    ) {
                        // Recurse to merge nested data
                        if (isset($newItem['data']) && is_array($newItem['data'])) {
                            if (!isset($existingItem['data']) || !is_array($existingItem['data'])) {
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
        elseif (is_string($parsed)) {
            $output .= $indent . $parsed . "\n";
        }

        // If it's a directive block like <IfModule>, <Files>, etc.
        elseif (is_array($parsed) && isset($parsed['type'], $parsed['rules'], $parsed['data'])) {
            $start = $indent . "<" . ucfirst($parsed['type']) . " " . $parsed['rules'] . ">\n";
            $end   = $indent . "</" . ucfirst($parsed['type']) . ">\n";

            $middle = '';
            foreach ($parsed['data'] as $item)
            {
                $middle .= $this->rebuild($item, $depth + 1);
            }

            $output .= $start . $middle . $end;
        }

        return $output;
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
        return $this->put($this->path, trim($isGenerated["data"]) . "\n");
    }    
}
