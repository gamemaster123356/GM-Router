# GM Router
A flexible and intuitive PHP router for handling routing and middleware in web applications.

<br>

## 🌟 Features
- **Routing:** Register routes for different HTTP methods and URIs.
- **Route Parameters:** Support for dynamic route parameters
- **Middleware:** Attach middleware functions to routes to handle authentication, validation, logging, etc.
- **Route Groups:** Group routes and assign common middleware to the group.
- **Route Redirect:** Easily redirect from one URI to another.
- **Error Handling:** Define custom error handlers for different HTTP status codes.
- **CSRF Protection:** Protect your forms from cross-site request forgery attacks.

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
