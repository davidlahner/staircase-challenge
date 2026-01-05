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
                    <textarea id="bbcode-result" readonly><?php echo htmlspecialchars($_GET['bbcode']); ?></textarea>
                    <button onclick="copyToClipboard()" class="copy-btn">Copy to Clipboard</button>
                </div>
            </div>
        <?php endif; ?>

        <form action="process.php" method="POST" class="challenge-form">
            <div class="form-group">
                <label for="username">BoardGameGeek Username:</label>
                <input type="text" id="username" name="username" required
                       value="<?php echo isset($_GET['username']) ? htmlspecialchars($_GET['username']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="from_date">From Date (yyyy-mm-dd):</label>
                <input type="date" id="from_date" name="from_date" required
                       value="<?php echo isset($_GET['from']) ? htmlspecialchars($_GET['from']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="to_date">To Date (yyyy-mm-dd):</label>
                <input type="date" id="to_date" name="to_date" required
                       value="<?php echo isset($_GET['to']) ? htmlspecialchars($_GET['to']) : ''; ?>">
            </div>

            <button type="submit" class="submit-btn">Generate Staircase Challenge</button>
        </form>
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
    </script>
</body>
</html>

