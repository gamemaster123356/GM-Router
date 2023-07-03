# GM Router
A flexible and intuitive PHP router for handling routing and middleware in web applications.

<br>

## 🌟 Features
- **Routing**: The router allows you to define routes by specifying the HTTP method(s), URI pattern, and the handler function or controller to be executed when a match is found.

- **Middleware**: You can attach middleware functions to routes or route groups. Middleware functions are executed before the route handler and can perform tasks such as authentication, authorization, input validation, etc.

- **Route Groups**: The router supports route grouping, allowing you to apply common middleware or prefix to a group of routes.

- **Named Routes**: You can assign names to routes, making it easier to generate URLs for specific routes.

- **Redirects**: The router provides a method to define route redirects, which redirect requests from one URI to another.

- **Error Handling**: The router allows you to define error handlers for different HTTP status codes. You can specify either a callback function or a file path to handle the errors.

- **Customizable**: The router supports various options such as specifying the directory for controller classes, middleware classes, CSRF token expiration time, and allowed referers for CSRF protection.

- **CSRF Protection**: The router includes CSRF token handling and validation to protect against cross-site request forgery attacks.

- **Dynamic Controller Resolution**: The router can resolve controller handlers in the format "Controller@method" to actual callable functions.

<br>

## 📘 Usage
1. Import the GM-Router library into your PHP file:
```php
include('PATH TO THE FOLDER WHERE gm-router.php IS STORED');
```

2. Create an instance of GMRouter class:
```php
$gmrouter = new GMRouter();
```

3. Configure your routes:
```php
$gmrouter->addRoute('GET', '/home', 'controller', 'HomeController@index');
$gmrouter->addRoute('POST', '/login', 'controller', 'AuthController@login');
```

3. Run the router to dispatch the appropriate route handler(NOTE: This line SHOULD be added AFTER adding all your routes):
```php
$gmrouter->dispatch();
```
