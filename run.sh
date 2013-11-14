#!/bin/bash -e

base_path="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

read -s -p "Coinbase API Key: " api_key
echo ""
COINBASE_API_KEY="$api_key" php "$base_path/coinbase-stop-loss.php"
