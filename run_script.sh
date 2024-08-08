#!/bin/bash

# Cloudflare API credentials
API_KEY="22e5824d7e297bf0b2cc71b371891cafab8a8"
EMAIL="admin@aljup.com"
ZONE_ID="2baa302be7dd0c6de7d839f2346c8ec4"

# Cloudflare API endpoint
DNS_RECORDS_URL="https://api.cloudflare.com/client/v4/zones/$ZONE_ID/dns_records"

# Fetch all DNS records
response=$(curl -s -X GET "$DNS_RECORDS_URL" -H "X-Auth-Email: $EMAIL" -H "X-Auth-Key: $API_KEY" -H "Content-Type: application/json")

# Extract record IDs
record_ids=$(echo "$response" | jq -r '.result[] | .id')

# Delete each record
for record_id in $record_ids; do
    delete_response=$(curl -s -X DELETE "$DNS_RECORDS_URL/$record_id" -H "X-Auth-Email: $EMAIL" -H "X-Auth-Key: $API_KEY" -H "Content-Type: application/json")
    echo "Deleted record ID: $record_id"
done

echo "تم حذف جميع سجلات DNS بنجاح"

