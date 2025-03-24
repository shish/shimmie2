# Caching in Shimmie

Shimmie puts a PSR-16 compatible cache in the `$cache` variable, and mostly
sticks to the basics of the interface:

- `$cache->get(string $key)`
- `$cache->set(string $key, mixed $value, int $ttl)`
- `$cache->delete(string $key)`

There is also an extra function in shimmie's utils rather than the cache
interface itself:

- `cache_get_or_set(string $key, callable $callback, int $ttl)`

Important notes:

- If you don't have a performance problem, you probably don't need to be caching things, and it's better to avoid the complexity.
- Even on large high-load sites, `post/list` and `post/view` get the overwhelming majority of requests, other pages will get a much smaller benefit from caching.
- `$cache` is always set, but by default it will be set to a no-op cache where `set()` does nothing and `get()` always returns `null`.
