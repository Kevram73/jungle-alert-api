# Authenticating requests

To authenticate requests, include an **`Authorization`** header with the value **`"Bearer Bearer {YOUR_TOKEN}"`**.

All authenticated endpoints are marked with a `requires authentication` badge in the documentation below.

This API uses Laravel Sanctum for authentication. You can obtain a token by making a POST request to `/api/v1/auth/login` with your credentials. Include the token in the Authorization header as `Bearer {token}`.
