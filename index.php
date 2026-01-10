<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BoardGameGeek Staircase Challenge</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>🎲 BoardGameGeek Staircase Challenge</h1>
        <p class="description">Generate BBCode for your staircase challenge based on your BoardGameGeek play history.</p>

        <?php if (isset($_GET['error'])): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['success']) && isset($_GET['bbcode'])): ?>
            <div class="success-message">
                <h2>Your Staircase Challenge BBCode:</h2>
                <div class="bbcode-output">
                    <label for="bbcode-result" style="font-weight: bold; display: block; margin-bottom: 5px;">Generated BBCode:</label>
                    <textarea id="bbcode-result" readonly><?php echo htmlspecialchars($_GET['bbcode']); ?></textarea>
                    <button onclick="copyToClipboard()" class="copy-btn">Copy to Clipboard</button>
                </div>
            </div>
        <?php endif; ?>

        <form action="process.php" method="POST" class="challenge-form" id="challenge-form">
            <div class="form-group">
                <label for="username">BoardGameGeek Username:</label>
                <input type="text" id="username" name="username" required
                       value="<?php echo isset($_GET['username']) ? htmlspecialchars($_GET['username']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="from_date">From Date:</label>
                <input type="date" id="from_date" name="from_date" required
                       value="<?php echo isset($_GET['from']) ? htmlspecialchars($_GET['from']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="to_date">To Date:</label>
                <input type="date" id="to_date" name="to_date" required
                       value="<?php echo isset($_GET['to']) ? htmlspecialchars($_GET['to']) : ''; ?>">
            </div>

            <button type="submit" class="submit-btn" id="submit-btn">
                <span class="btn-text">Generate Staircase Challenge</span>
                <span class="loading-spinner" style="display: none;">
                    <svg class="spinner" viewBox="0 0 50 50">
                        <circle class="path" cx="25" cy="25" r="20" fill="none" stroke="#ffffff" stroke-width="5" stroke-linecap="round" stroke-dasharray="31.416" stroke-dashoffset="31.416">
                            <animate attributeName="stroke-array" dur="2s" values="0 31.416;15.708 15.708;0 31.416" repeatCount="indefinite"/>
                            <animate attributeName="stroke-dashoffset" dur="2s" values="0;-15.708;-31.416" repeatCount="indefinite"/>
                        </circle>
                    </svg>
                    Generating...
                </span>
            </button>
        </form>
        <a href="https://boardgamegeek.com/" target="_blank"><img src="powered_by_bgg_logo.jpg" alt="Powered by BoardGameGeek"/></a>
    </div>

    <script>
        function copyToClipboard() {
            const textarea = document.getElementById('bbcode-result');
            textarea.select();
            document.execCommand('copy');

            const btn = document.querySelector('.copy-btn');
            const originalText = btn.textContent;
            btn.textContent = 'Copied!';
            setTimeout(() => {
                btn.textContent = originalText;
            }, 2000);
        }

        // Handle form submission with loading state
        document.getElementById('challenge-form').addEventListener('submit', function() {
            const submitBtn = document.getElementById('submit-btn');
            const btnText = submitBtn.querySelector('.btn-text');
            const spinner = submitBtn.querySelector('.loading-spinner');

            // Show loading state
            btnText.style.display = 'none';
            spinner.style.display = 'inline-flex';
            submitBtn.disabled = true;
            submitBtn.style.cursor = 'not-allowed';

            // Optional: Add a timeout fallback in case something goes wrong
            setTimeout(() => {
                if (submitBtn.disabled) {
                    btnText.style.display = 'inline';
                    spinner.style.display = 'none';
                    submitBtn.disabled = false;
                    submitBtn.style.cursor = 'pointer';
                }
            }, 60000); // 60 second timeout
        });
    </script>
</body>
</html>

