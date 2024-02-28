<?php

use Diglactic\Breadcrumbs\Breadcrumbs;
use Diglactic\Breadcrumbs\Generator as BreadcrumbTrail;

Breadcrumbs::for('embedded-products.reports', function (BreadcrumbTrail $trail) {
    $trail->push('Embedded Products', route('embedded-products.index'));
    $trail->push('Reports', route('embedded-products.reports'));
});

Breadcrumbs::for('embedded-products.reports.certificates', function (BreadcrumbTrail $trail, $ep) {
    $trail->push('Embedded Products', route('embedded-products.index'));
    $trail->push('Reports', route('embedded-products.reports'));
    $trail->push($ep->product_name, route('embedded-products.reports.certificates', $ep->id));
});
