<?php

// Set the stop loss trigger price here. If the price drops to this amount
// or below, the bot will sell all your BTC at the market price. (Make sure
// this is a string value.)
define('STOP_LOSS_PRICE', '350.00');

// The number of seconds between price checks.
define('SECONDS_BETWEEN_CHECKS', 60);

// A list of email addresses, comma separated, to notify if the stop loss
// is triggered. Don't forget that most cell phone carriers have email
// addresses for your text number, too!
define('NOTIFY_EMAIL', 'youremail@domain.com, 1234567890@vtext.com');

// ===========================================================================
//     NO MORE CONFIGURATION REQUIRED BEYOND THIS POINT
// ===========================================================================

// Remove execution time limit (daemon script).
set_time_limit(0);

// Bring in the Coinbase API library.
require_once('coinbase-php/lib/Coinbase.php');
$coinbase = new Coinbase(getenv('COINBASE_API_KEY'));

// Grab the account balance to both verify the API is working and to remind the
// user that our finger is on the button...
$balance = $coinbase->getBalance();

echo "Coinbase stop loss bot started.\n";
echo "Rechecking sell price every " . SECONDS_BETWEEN_CHECKS . " seconds.\n";
echo "Stop loss price set at \$" . STOP_LOSS_PRICE . ".\n";
echo "Prepared to sell entire account balance ($balance BTC).\n";

while (true) {
    try {
        // Check the current sell price. This includes the Coinbase fees of $0.15 + 1%.
        $sellPrice = $coinbase->getSellPrice('1');
        echo date('[Y-m-d H:i:s]') . " \$$sellPrice";

        // If the current sell price fell below our stop loss price, SELL SELL SELL!
        if (bccomp($sellPrice, STOP_LOSS_PRICE) <= 0) {
            echo "\n";
            echo "!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!\n";
            echo "!!! STOP LOSS TRIGGERED, SELLING ALL BTC !!!\n";
            echo "!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!\n";

            // Retrieve the current account balance.
            $balance = $coinbase->getBalance();
            echo "Current account balance is $balance BTC.\n";

            // Fire the missiles.
            $response = $coinbase->sell($balance);
            $amountSold = $response->transfer->btc->amount;
            $saleSubtotal = $response->transfer->subtotal->amount;
            $saleTotal = $response->transfer->total->amount;
            $saleCode = $response->transfer->code;

            // Set up our notification message.
            $subject = 'Coinbase Stop Loss Triggered';
            $message = <<<EOT
The bot sold all your BTC.

Amount Sold: $amountSold BTC
Cash Out: \$$saleSubtotal (\$$saleTotal after fees)
Transaction Code: $saleCode

The bot will now exit. If you wish to resume its operation, you need to restart it.
EOT;

            echo "Successfully sold $amountSold BTC.\n";
            echo "Cashed out for \$$saleSubtotal (\$$saleTotal after fees).\n";
            echo "The transaction code is $saleCode.\n";

            // Send an email notification that the stop loss was triggered.
            if (mail(NOTIFY_EMAIL, $subject, $message)) {
                echo "Successfully sent email notification to " . NOTIFY_EMAIL . ".\n";
            } else {
                echo "Failed to send email notification to " . NOTIFY_EMAIL . ".\n";
            }

            // Quit the stop loss bot. We fired the missiles. Nothing left to do.
            break;
        } else {
            echo " (no action)\n";
        }
    } catch (Exception $e) {
        // Print it and try again.
        echo "EXCEPTION: " . $e->getMessage() . "\n";
    }

    // Go to sleep for a while.
    sleep(SECONDS_BETWEEN_CHECKS);
}

echo "Coinbase stop loss bot exiting.\n";

?>
