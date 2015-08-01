<?php
print("Shimmie Auto-Test\n");
print("~~~~~~~~~~~~~~~~~\n");
print("Database   : " . getenv("DB") . "\n");
print("PHP        : " . phpversion() . "\n");
print("PDO drivers: " . var_export(PDO::getAvailableDrivers(), true) . "\n");
