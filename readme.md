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

### Settings
```php
$olapHelper->setConfig('column', 'string|int|array(float, 2)|round|ceil');
$olapHelper->setSort('column', 'asc|desc');
$olapHelper->setLimit(array(0, 10));
```

### Methods
```php
$olapHelper->getColumnsIndex();
$olapHelper->getColumnsIndexCount();
$olapHelper->getColumnsName();
$olapHelper->getColumnsNameCount();
$olapHelper->getColumns();
$olapHelper->getColumnsCount();
$olapHelper->getRowsCount();
$olapHelper->getDataColumn(column);
$olapHelper->getData(columns);
```
