# VoltMosaic

VoltMosaic simulates mobile recharge and electricity or internet bill payments. The web client uses Bootstrap, HTML, CSS, and JavaScript. PHP validates each request and stores transactions in MySQL. Repeating a request ID with the same details returns the original receipt.

## Web setup

Use PHP 8.1+ with PDO MySQL and MySQL 8. Import the schema with an account that can create the demo database.

```sh
mysql -u root -p < schema.sql
```

Create a local MySQL account with SELECT and INSERT permissions on the payments table and UPDATE permission on its id column for the idempotent insert. Set its credentials in `VOLT_USER` and `VOLT_PASSWORD`. The default DSN points to the local voltmosaic database. Override it with `VOLT_DSN` if needed.

```sh
php -S 127.0.0.1:8031 -t public
```

Open http://localhost:8031 and use a sample reference such as DEMO-1001. GET /api.php returns recent transactions. POST /api.php accepts JSON with service, reference, integer amount in yen, and a lowercase UUID request_id.

## Android companion

Import VoltMosaic.aia in MIT App Inventor and use the Companion or export an APK. The companion opens the hosted web app inside a WebViewer so its normal JavaScript client and backend work together. Enter a trusted HTTPS URL where the demo is running and tap Open VoltMosaic. The phone needs network access to that host. Use an authenticated or private demo environment when exposing the service beyond localhost.

The Android project is supplied as importable source. Its import and device execution require MIT App Inventor.
