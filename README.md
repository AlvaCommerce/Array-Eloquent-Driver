# Array Eloquent Driver

The `Array Eloquent Driver` package provides an array-based implementation of Laravel's Eloquent ORM. This package allows you to use arrays as the data source for your Eloquent models, making it easier to work with in-memory data or mock data for testing purposes.

## Installation

To install the package, use Composer:

``` bash
  composer require alva/array-eloquent-driver
```

## Configuration

After installing the package, you need to register the service provider in your `config/app.php` file:

```php
'providers' => [
    // Other service providers...
    Alva\ArrayEloquentDriver\ArrayEloquentDriverServiceProvider::class,
],
```

## Usage

To use the array-based Eloquent driver, extend your models from `ArrayModel`:

```php
use Alva\ArrayEloquentDriver\Database\Eloquent\ArrayModel;

class YourModel extends ArrayModel
{
    // Your model code...
    
    public static function getResolverClassName(): string
    {
        return '...'; // Your resolver class
    }

    public static function getResolverHandler(): string
    {
        return '...'; // Your resolver handler
    }
}
```

You can define relationships and other Eloquent features as usual. For example, to define a `HasOne` relationship:

```php
use Illuminate\Database\Eloquent\Relations\HasOne;

class YourModel extends ArrayModel
{
    public function instructor(): HasOne
    {
        $connection = app(RelationModel::class)->getConnection();
        return $this->setConnection($connection->getName())->hasOne(RelationModel::class);
    }
}
```

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).
```
