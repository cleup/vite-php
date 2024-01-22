# Cleup Vite integration

Library for using Vite in your `php` or `Cleup` project

#### Installation

1. Install the `cleup/vite-php` library using composer:

```
composer require cleup/vite-php
```

2. Create a `Vite` project ([https://vitejs.dev/guide/](https://vitejs.dev/guide/)):

```
npm create vite@latest
```

3. Install the `cleup/vite-plugin` plugin ([https://github.com/cleup/vite-plugin](https://github.com/cleup/vite-plugin)):

```
npm i cleup/vite-plugin
```

#### Usage

Configure `vite.config.js`:

```js
// vite.config.js
import { defineConfig } from "vite";
import cleup from "cleup-vite-plugin";

export default defineConfig({
    plugins: [
        cleup([
            "assets/js/app.js"
        ])
    ],
});
```

Create a new instance of the Vite class in a convenient location:

```php
// header.php
use Cleup\Foundation\Vite;

$vite = new Vite([
    /*
       Force development mode.
       By default, this parameter will change automatically depending on the state of the development server.
    */
    'dev' => false,

    /*
        The build directory (default: 'build')
    */
    'buildDir' => 'build',
]);
```
Put the `use` method inside the `head` tag of your document:

```php
<head>
    ...
    <?= $vite->use('assets/js/app.js'); ?>
</head>
```

Start the `node js` development server
```
npm run dev
```

Opening the project page we will see the following code:
```html
<head>
    ...
    <!-- If the development mode -->
    <script  type="module" src="http://127.0.0.1:5173/@vite/client"></script>
    <script  type="module" src="http://127.0.0.1:5173/assets/js/app.js"></script>
    <!-- If the mode of production-->
    <script  type="module" src="/build/assets/app-AOYsxs3O.js"></script>
</head>
```

#### Use styles
##### Importing styles into `app.js`:
```js
// assets/styles/app.js
import '../styles/app.css';
...
```

Use `app.js` and the style file will be attached automatically:
```php
<head>
    ...
    <?= $vite->use('assets/js/app.js'); ?>
</head>
```
Output
```html
<head>
    ...
    <!-- If the development mode -->
    <script  type="module" src="http://127.0.0.1:5173/@vite/client"></script>
    <script  type="module" src="http://127.0.0.1:5173/assets/js/app.js"></script>
    <style type="text/css" data-vite-dev-id="path/to/assets/styles/app.css">body {background-color: #6294ff}</style>
    <!-- If the mode of production-->
    <link rel="stylesheet" type="text/css" href="/build/assets/app-WNmNaalN.css" />
    <script  type="module" src="/build/assets/app-AOYsxs3O.js"></script>
</head>
```

##### Use the style with `cleup-vite-plugin`
```js
// vite.config.js
...
export default defineConfig({
    plugins: [
        cleup([
            "assets/js/app.js",
            "assets/styles/example.scss", // Pre-install the sass pre-processor
            "assets/styles/root.css",
        ])
    ],
});
```
Using this method in the document file you will need to explicitly set the entry point:
```php
<head>
    ...
    <?php /* The standard method */?>
    <?= $vite->use('assets/styles/root.css'); ?>
    <?= $vite->use('assets/js/example.scss'); ?>
    <?= $vite->use('assets/js/app.js'); ?>

    <?php /* To simplify development, use an array with entry points */?>
    <?= $vite->use([
      'assets/styles/root.css',
      'assets/js/example.scss',
      'assets/js/app.js',
    ]); ?>
</head>
```

#### Tag Attributes
A simple method for changing attributes:

```php
<head>
    ...
    <?= $vite->use('assets/styles/root.css', [
        'type' => 'text/css'
    ]); ?>

    <?= $vite->use('assets/js/app.js', [
        'key' => 'value',
        ...
    ]); ?>
   ...
</head>
```

Using an associative array:


```php
<head>
    ...
    <?= $vite->use([
        'assets/js/app.js' => [
            'type' => 'text/css'
        ],
        'assets/js/app.js' => [
            'key' => 'value'
        ]
        'example.scss' // Standard attributes or those specified by the $attributes parameter will be used
    ]); ?>
    ...
</head>
```