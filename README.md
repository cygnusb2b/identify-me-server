# Identify Me! Server
Symfony-based server API for user identification components. Serves the backend needs of both the library and the admin.

## Installation
Clone the repository and install the dependencies.
```
composer install
```

## Development
Once the dependencies are installed, you can run the server by executing:
```
bin/console server:run localhost:8000
```
API requests can now be accessed via `http://localhost:8000`. By default, the application will run in `dev` mode. This can be changed by setting the `APP_ENV` environment variable to `prod`.

