# NextERP 0.66.1

- Mobile API login no longer calls the protected `Request::getContent()` method.
- JSON request data is read through the public Nextcloud `IRequest::getParams()` API.
- App version updated to 0.66.1.
