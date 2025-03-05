# Subito.it Scraper



A simple scraper for [subito.it](https://www.subito.it/) built with Laravel and **chromephp** using **Chromium** in headless mode.  
Currently, it can extract *title*, *price*, *location*, *upload date*, and *condition* from a single page.  
These data will later be used to create price trends and help identify the best time to buy an item.


## Quick Setup
1. Clone the repository
2. Install PHP dependencies: `composer install`

## Usage

Use the following Artisan command to scrape the page:

```bash
php artisan scrape:prices {url} {--head}
```
- **{url}**: The page URL to scrape
- **--head**: Disables headless mode, showing the browser activity

Example:
```bash
php artisan scrape:prices "https://www.subito.it/annunci-veneto/vendita/usato/?q=kawasaki%20z800"
``` 
