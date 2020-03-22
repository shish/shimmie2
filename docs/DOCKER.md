# Docker

If you just want to run shimmie inside docker, there's a pre-built image
in dockerhub - `shish2k/shimmie2` - which can be used like:
```
docker run -p 8000 -v /my/hard/drive:/app/data shish2k/shimmie2
```

If you want to build your own image from source:
```
docker build -t shimmie .
```

There are various options settable with environment variables:
- `UID` / `GID` - which user ID to run as (default 1000/1000)
- `INSTALL_DSN` - specify a data source to install into, to skip the installer screen, eg
  `-e INSTALL_DSN="pgsql:user=shimmie;password=6y5erdfg;host=127.0.0.1;dbname=shimmie"`

