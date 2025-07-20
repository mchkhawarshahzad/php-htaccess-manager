# HtaccessManager

A PHP class to read, parse, merge, and update `.htaccess` files in a structured way.

## 🔧 Features

- Parse existing `.htaccess` into nested blocks  
- Merge new rules safely without duplicates  
- Rebuild `.htaccess` content in proper structure  
- Simple `get()` and `put()` file methods  
- Auto-checks for disabled PHP functions  
- Update `.htaccess` with new rules without affecting existing ones
- Apache rules validation and conversion on the fly based on apache version, if V2.2 then (2.4+ to 2.2) and if 2.4+ then (2.2 to 2.4+)

## 📂 Usage Examples (in `examples` folder)

- `get` – Fetch raw `.htaccess` file data  
- `put` – Save raw data to `.htaccess` file  
- `generate` – Generate new `.htaccess` content using existing and new rules  
- `update` – Merge new rules safely without duplication and update the file  

### ✅ Example

If installed via Composer, there's no need to include the file manually.

require_once __DIR__ . '/../src/Htaccess.php';

just use as

use HtaccessManager\Htaccess;

$ht = new Htaccess();

For merge and update

$response = $ht->update("new rules array here");

For get raw content (New rules are optional)

$response = $ht->generate("New rules array here",".htaccess file path here");

## 📦 Installation

```bash
composer require mchkhawarshahzad/php-htaccess-manager
