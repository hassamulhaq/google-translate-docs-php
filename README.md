Start project
```text
composer start
```

API to translate
```text
http://localhost:8000/translate
```


### .env
```dotenv
UPLOAD_DIR_DOCUMENT="uploads/"

GOOGLE_PROJECT_ID=
GOOGLE_KEY=
GOOGLE_APPLICATION_CREDENTIALS=service-account.json
GOOGLE_LOCATION_ID="global"
TARGET_LANGUAGE_CODE=fr
```

### create `service-account.json` file at root level
```json
{
  "type": "service_account",
  "project_id": "",
  "private_key_id": "",
  "private_key": "-----BEGIN PRIVATE KEY-----\nyour-private-key\n-----END PRIVATE KEY-----\n",
  "client_email": "your-client-email@your-project-id.iam.gserviceaccount.com",
  "client_id": "your-client-id",
  "auth_uri": "https://accounts.google.com/o/oauth2/auth",
  "token_uri": "https://oauth2.googleapis.com/token",
  "auth_provider_x509_cert_url": "https://www.googleapis.com/oauth2/v1/certs",
  "client_x509_cert_url": "https://www.googleapis.com/robot/v1/metadata/x509/your-client-email%40your-project-id.iam.gserviceaccount.com"
}
```
and update env file with `GOOGLE_APPLICATION_CREDENTIALS=service-account.json`

## Postman preview
```text
requests[document]=file
requests[target_language_code]=fr
```
![screenshot](preview.png)

___
