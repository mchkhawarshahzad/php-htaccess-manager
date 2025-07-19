# HtaccessManager
A PHP class to read, parse, merge, and update `.htaccess` files in a structured way.

## 🔧 Features

- Parse existing `.htaccess` into nested blocks
- Merge new rules safely without duplicates
- Rebuild `.htaccess` content in proper structure
- Simple `get()` and `put()` file methods
- Auto-checks for disabled PHP functions
- Update for save new rules to htaccess file with existing rules

## 📦 Installation

```bash
composer require mchkhawarshahzad/php-htaccess-manager

## Usage Example in examples folder
- get for fetch .htaccess file raw data
- put for save raw data to .htaccess file
- generate for raw data generation with htaccess existing and new rules which given by you.
- update for add/update data to htaccess with merge new rules, safely without duplicates

If installed via composer then no need to include this line in your code
require_once __DIR__ . '/../src/Htaccess.php';
just use as
use HtaccessManager\Htaccess;
$ht = new Htaccess();

For merge and update
$response = $ht->update("new rules array here");
For get raw content (New rules are optional)
$response = $ht->generate("New rules array here",".htaccess file path here");
