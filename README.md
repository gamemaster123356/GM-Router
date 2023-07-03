# GM Router
GM Router is a powerful and user-friendly PHP router designed to handle routing and middleware in web applications. With its flexible and intuitive features, it provides an efficient way to define routes and apply middleware functions. Whether you're building a small website or a large-scale web application, GM Router simplifies the routing process and enhances the overall development experience. **It is compatible with PHP versions 5.6 and above, including PHP 7.x and PHP 8.x**.

<br>

## 🌟 Features
- **Routing:** The router allows you to define routes by specifying the HTTP method(s), URI pattern, and the handler function or controller to be executed when a match is found.

- **Dynamic Route Parameters with Regex Support:** This allows for the definition of dynamic route parameters with regex support. This feature enhances routing by enabling custom constraints on parameter values within URI patterns.

- **Route Groups:** The router supports route grouping, allowing you to apply common middleware or prefix to a group of routes.

- **Named Routes:** You can assign names to routes, making it easier to generate URLs for specific routes.

- **Middleware:** You can attach middleware functions to routes or route groups. Middleware functions are executed before the route handler and can perform tasks such as authentication, authorization, input validation, etc.

- **Middleware Grouping:** This enables you to group and apply multiple middleware functions together, simplifying the application of shared logic to specific sets of routes or route groups. This feature enhances code reusability and maintainability by providing an organized approach to managing middleware stacks.

- **Redirects:** The router provides a method to define route redirects, which redirect requests from one URI to another.

- **Error Handling:** The router allows you to define error handlers for different HTTP status codes. You can specify either a callback function or a file path to handle the errors.

- **Customizable:** The router supports various options such as specifying the directory for controller classes, middleware classes, CSRF token expiration time, and allowed referers for CSRF protection.

- **CSRF Protection:** The router includes CSRF token handling and validation to protect against cross-site request forgery attacks.

- **Dynamic Controller Resolution:** The router can resolve controller handlers in the format "Controller@method" to actual callable functions.

<br>

## 📘 Usage
1. Add these lines to you .htaccess file(Replace index.php with the path of the file which is going to have the routes) If you want to specify a different location for the router to place the routes, modify the RewriteBase directive (NOTE: If you are using Nginx, please refer to the Nginx documentation for configuring URL rewriting with PHP):
```php
RewriteEngine On
RewriteBase /
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

2. Import the GM-Router library into your PHP file:
```php
include('THE PATH WHERE gm-router.php IS STORED');
```

3. Create an instance of GMRouter class:
```php
$gmrouter = new GMRouter();
```

4. Configure your routes:
```php
$gmrouter->addRoute('GET', '/home', 'controller', 'HomeController@index');
$gmrouter->addRoute('POST', '/login', 'controller', 'AuthController@login');
```

5. Run the router to dispatch the appropriate route handler(NOTE: This line SHOULD be added AFTER adding all your routes):
```php
$gmrouter->dispatch();
```

<br>

## 📄 License
GM Router is licensed under the GNU GPL v3. You can find the full license [here](https://github.com/gamemaster123356/GM-Router/blob/main/LICENSE).
