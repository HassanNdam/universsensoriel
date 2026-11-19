# Webhooks Stripe en local : transmet les evenements du compte de test au site.
# A laisser tourner pendant les essais de paiement. Arret : Ctrl+C.
# La cle est lue dans le .env ; la CLI Stripe du poste peut etre connectee a un autre compte.
$racine = Split-Path -Parent $PSScriptRoot
$env_ = Get-Content (Join-Path $racine '.env')
$cle = ($env_ | Select-String "^STRIPE_SECRET_KEY='(.+)'").Matches[0].Groups[1].Value
$site = ($env_ | Select-String "^WP_HOME='(.+)'").Matches[0].Groups[1].Value
stripe listen --api-key $cle --all-snapshot --forward-to "$site/?wc-api=wc_stripe"
