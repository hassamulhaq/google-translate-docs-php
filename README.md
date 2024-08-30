### composer install
```shell
composer install
composer start

# visit
`http://localhost:8000`

# restart project
1. execute command: ./sh__kill_ports.sh
2. composer start
```

### Start project
```text
composer start
```

### API to translate
```text
http://localhost:8000/translate-file
```

### change the app url in `.env` file, it will be used to generate the download link
```text
APP_URL=http://localhost:8000
```


### .env
```dotenv
UPLOAD_DIR_DOCUMENT="uploads/"

GOOGLE_PROJECT_ID=
GOOGLE_KEY=
GOOGLE_APPLICATION_CREDENTIALS=service-account.json
GOOGLE_LOCATION_ID="global"
TARGET_LANGUAGE_CODE=ur
```

### create `service-account.json` file at root level
```json
{
  "type": "service_account",
  "project_id": "",
  "private_key_id": "",
  "private_key": "-----BEGIN PRIVATE KEY-----\f??????????????????ZpSjgg==\n-----END PRIVATE KEY-----\n",
  "client_email": "??-translator@?????.gserviceaccount.com",
  "client_id": "",
  "auth_uri": "https://accounts.google.com/o/oauth2/auth",
  "token_uri": "https://oauth2.googleapis.com/token",
  "auth_provider_x509_cert_url": "https://www.googleapis.com/oauth2/v1/certs",
  "client_x509_cert_url": "https://www.googleapis.com/robot/v1/metadata/x5??/??-translator%40???????-s2.iam.gserviceaccount.com",
  "universe_domain": "googleapis.com"
}
```
and update env file with `GOOGLE_APPLICATION_CREDENTIALS=service-account.json`


### Postman endpoint
```text
postman endpoint: `http://localhost:8000/translate-file` [POST]
--------------------------------------------------
[type:file]    requests[document]=file.doc
[type:string]  requests[target_language_code]=mn
--------------------------------------------------
```

### .htaccess
```apacheconf
RewriteEngine On

# Redirect all non-file and non-directory requests to router.php
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ /router.php [L]

# Allow access to specific file types
<FilesMatch "\.(css|js|png|jpg|jpeg|gif|svg|ico|woff|woff2|ttf|eot|otf|json|xml|txt|doc|docs|docx|pdf|csv|html)$">
    Require all granted
</FilesMatch>

# Deny access to hidden files and directories (e.g., .env, .git)
<FilesMatch "^\.">
    Require all denied
</FilesMatch>

# Deny access to service-account.json and other sensitive files
<FilesMatch "(^|/)(service-account\.json|\.env)$">
    Require all denied
</FilesMatch>

# Allow access to router.php, index.php, and other necessary PHP files
<FilesMatch "^(router|index)\.php$">
    #Require all granted
</FilesMatch>

# Deny access to everything else
#<FilesMatch ".*\.(php|html|json)$">
#    Require all denied
#</FilesMatch>

<Files "service-account.json">
  Order Allow,Deny
  Deny from all
</Files>
```

### Postman preview
```text
requests[document]=file
requests[target_language_code]=fr
```
![preview](samples/preview.png)

![preview02](samples/preview02.png)

![preview03](samples/preview03.png)

#### (Before) Raw docs - samples are in /sample dir
`/samples/devhassam.doc`
![preview04](samples/preview04.png)

#### (After) Translated docs - samples are in /sample dir
`/samples/66ced9d1adeb2-translated-fa9b14194af3f545a97c2626cf976f142625bbae5bdf9eaaad1be05d61379422-92defdb9ec2a0125.doc`
![preview05](samples/preview05.png)

___


### 
```text
Create and Download Service Account Credentials:

Go to the Google Cloud Console.
Navigate to IAM & Admin > Service Accounts.
Create a new service account or use an existing one.
Generate a new JSON key file and download it.

1. Assign the Correct Permissions:
Step 1: Have your client log in to their Google Cloud Console.
Step 2: Navigate to IAM & Admin > IAM.
Step 3: Locate the service account that corresponds to the JSON key you received.
Step 4: Ensure that this service account has the Cloud Translation API User role, which grants permissions to perform translation operations. If this role is not assigned, your client should add it.
Here's how to add the role:
Click on the "Edit" icon next to the service account.
Click on "Add Another Role."
Select Cloud Translation API User from the role list.
Save the changes.
2. Regenerate the JSON Key:
Step 1: After updating the permissions, your client should regenerate the JSON key for the service account.
Step 2: This can be done by navigating to IAM & Admin > Service Accounts, selecting the relevant service account, and then clicking Create Key.
Step 3: Download the new JSON key file.
```
