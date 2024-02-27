Vibes:
======
Generally-useful extensions are great, custom extensions and themes just for one specific DIY site
are welcome too. I (Shish) will probably only actively maintain and add features to the extensions
which I personally use, but if you submit some code of your own I will try to keep it updated and
compatible with any API changes that come along. If your code comes with unit tests, this type of
maintenance is much more likely to be successful :)

Testing:
========
Github Actions will be running three sets of automated tests, all of which you can run for yourself:

- `composer format` - keeping a single style for the whole project
- `composer test` - unit testing
- `composer stan` - type checking

The `main` branch is locked down so it can't accept pull requests that don't pass these

Testing FAQs:
=============

## What the heck is "Method XX::YY() return type has no value type specified in iterable type array."?

PHP arrays are very loosely defined - they can be lists or maps, with integer or string
(or non-continuous integer) keys, with any type of object (or multiple types of object).
This isn't great for type safety, so PHPStan is a bit stricter, and requires you to
specify what type of array it is and what it contains. You can do this with PHPdoc comments,
like:

```php
/**
 * @param array<string, Cake> $cakes -- a mapping like ["sponge" => new Cake()]
 * @return array<Ingredient> -- a list like [new Ingredient("flour"), new Ingredient("egg")]
 */
function get_ingredients(array $cakes, string $cake_name): array {
    return $cakes[$cake_name]->ingredients;
}
```
