# olapHelper for Codeigniter olap

[Developer - Robert Slooo](http://codbro.com)

## Description
This helper to work with Codeigniter for [ci_olap](https://github.com/mikifus/ci_olap)
<br />
PHP 5.3+

## Instruction

### Before
install [ci_olap](https://github.com/mikifus/ci_olap)

### Include
Array **$olap** result ci_olap

```php
$olapHelper = new olapHelper($olap);
```

### Methods
```php
$olapHelper->getDataRows();
$olapHelper->getDataColumn(column);
$olapHelper->getDataColumns(columns = false);
```
