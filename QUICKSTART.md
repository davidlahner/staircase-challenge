# Quick Start Guide

## 🚀 Getting Started in 3 Steps

### 1. Start the Server

#### Option A: PHP Built-in Server (Recommended for local testing)
```bash
cd /Users/egd/dev/git-repos/staircase-challenge
php -S localhost:8000
```

Then open your browser to: **http://localhost:8000**

#### Option B: XAMPP/MAMP/WAMP
- Copy the folder to your web server's htdocs/www directory
- Access via: **http://localhost/staircase-challenge/**

#### Option C: Production Server
- Upload all files to your web server
- Ensure PHP 7.0+ is installed
- Make sure `allow_url_fopen` is enabled in php.ini
- Access via your domain

### 2. Test the Installation

Visit the test page to verify API connectivity:
**http://localhost:8000/test.php**

This will test the BoardGameGeek API connection and display sample data.

### 3. Generate Your Staircase

1. Go to **http://localhost:8000**
2. Enter your BoardGameGeek username
3. Select a date range (e.g., 2024-01-01 to 2024-12-31)
4. Click "Generate Staircase Challenge"
5. Copy the BBCode output

## 📋 Example Usage

**Input:**
- Username: `YourBGGUsername`
- From: `2024-01-01`
- To: `2024-12-31`

**Output BBCode:**
```
01. 🎲[thing=432][/thing] (9)
02. 🎲🎲[thing=350933][/thing] (9)
03. 🎲🎲🎲[thing=21389][/thing] (10)
04. 🎲🎲🎲🎲[thing=219513][/thing] (10)
```

## 🔧 Troubleshooting

### "Failed to connect to BoardGameGeek API"
- Check your internet connection
- Verify `allow_url_fopen` is enabled in PHP
- Try again later (BGG API might be temporarily down)

### "Username not found"
- Double-check the spelling of the username
- Verify the username exists on BoardGameGeek.com

### "No plays found"
- Adjust your date range
- Make sure you logged plays during that period on BGG

### PHP Server Won't Start
```bash
# Check if PHP is installed
php --version

# Check if port 8000 is already in use
lsof -i :8000

# Try a different port
php -S localhost:8080
```

## 📱 Features

- ✅ Mobile-responsive design
- ✅ One-click copy to clipboard
- ✅ Real-time error messages
- ✅ Form data persistence
- ✅ Clean, modern UI

## 🎯 Next Steps

After generating your BBCode:
1. Copy the BBCode output
2. Post it to BoardGameGeek forums
3. Share your staircase challenge!

## 💡 Tips

- Use longer date ranges for more varied results
- The staircase stops when no game has enough plays
- Games are sorted alphabetically when play counts match
- Each game can only appear once in the staircase

## 📞 Need Help?

Check the README.md for detailed documentation or IMPLEMENTATION.md for technical details.

---

**Ready to start?** Run `php -S localhost:8000` and visit http://localhost:8000 🎲

