# ITVB23OWS Development Pipelines starter code

This repository contains starter code for the course ITVB23OWS Development pipelines,
which is part of the HBO-ICT program at Hanze University of Applied Sciences in
Groningen.

This is a deliberately poor software project, containing bugs and missing features. It
is not intended as a demonstration of proper software engineering techniques.

The application contains PHP 8.3.1 code and should run using the built-in PHP server,
which can be started using the following command.

```
php -S localhost:8000
```

In addition to PHP 8.3.1 or higher, the code requires the mysqli extension and a MySQL
or compatible server. The application assumes a root user without password, and tries
to access the database `hive`. The file `hive.sql` contains the database schema.

This application is licensed under the MIT license, see `LICENSE.md`. Questions
and comments can be directed to
[Ralf van den Broek](https://github.com/ralfvandenbroek).