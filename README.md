# BoardGameGeek Staircase Challenge Generator

A PHP web application that generates BBCode for BoardGameGeek staircase challenges based on your play history.

## Features

- Fetches play data from BoardGameGeek XML API (with pagination)
- Implements staircase logic (games with increasing play counts)
- Skips incomplete plays (where `incomplete != 0`)
- Lets you choose your own emoji or BBCode for the staircase (default: 🎲)
- Generates formatted BBCode output
- Handles duplicate play counts alphabetically
- Error handling for invalid usernames
- Responsive web design
- One-click copy to clipboard
- Loading spinner while generating staircase

## Files

- `index.php` - Main page with form and results display
- `process.php` - Backend processing and API integration
- `style.css` - Styling and responsive design

## Requirements

- PHP 7.0 or higher
- `allow_url_fopen` enabled (for API calls)
- SimpleXML extension (usually enabled by default)

## Installation

1. Clone or download this repository
2. Place files in your web server directory
3. Ensure PHP is installed and configured
4. Access `index.php` in your web browser

## Usage

1. Enter your BoardGameGeek username
2. Select date range (from and to dates in yyyy-mm-dd format)
3. Enter your preferred emoji or BBCode for the staircase (default: 🎲)
4. Click "Generate Staircase Challenge"
5. Wait for the loading spinner to finish
6. Copy the generated BBCode

## Staircase Logic

The staircase algorithm works as follows:

1. Fetches all plays for the user in the specified date range (fetches all pages)
2. Skips any play where the `incomplete` attribute is not 0
3. Aggregates plays by game (counting total plays per game)
4. Sorts games by play count (ascending), then alphabetically (ascending)
5. For each step (1, 2, 3, 4...):
   - Finds the first unused game with at least that many plays
   - Adds it to the staircase
   - If no game has enough plays, stops the staircase

## Output Format

```
01. 🎲[thing=432][/thing] (9)
02. 🎲🎲[thing=350933][/thing] (9)
03. 🎲🎲🎲[thing=21389][/thing] (10)
04. 🎲🎲🎲🎲[thing=219513][/thing] (10)
```

- Step number (zero-padded)
- Your chosen emoji or BBCode repeated by step count
- BBCode thing tag with game ID
- Play count in parentheses

## Error Handling

The application handles:
- Missing or invalid form inputs
- Invalid date formats
- Non-existent usernames
- API connection failures
- Empty play data
- XML parsing errors

## Local Development

For local testing, you can use PHP's built-in server:

```bash
php -S localhost:8000
```

Then visit http://localhost:8000 in your browser.

## API Reference

BoardGameGeek XML API2: https://boardgamegeek.com/xmlapi2/plays

Parameters:
- `username` - BGG username
- `mindate` - Start date (yyyy-mm-dd)
- `maxdate` - End date (yyyy-mm-dd)
- `page` - Page number (pagination)

## License

Free to use and modify.
