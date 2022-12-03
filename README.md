# Stripe Multiple Accounts

Support multiple stripe accounts

One user can have many "customers_id"

<br>

## Tables
<br>

## _ServiceIntegrations_ 
morph relation that contains the 
<br>
<br>

- stripe key
- stripe secret
- webhook secret

That means that the one model can manage one or more stripe accounts

In this case we have a "stub" files to create a implementation with Company Subsidiaries

<br>

## _UserServiceIntegrationAccount_ 
one to many relationship.
<br>
<br>

In this table we relate the user_id with the service_integration_id (Stripe or Another..) and with a extra column that contains the "real relation" or the "multiplicity" (account_id)


