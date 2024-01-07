<?php

declare(strict_types=1);

use function PHPStan\dumpType;

/** @param array{t: 1}|array{t: 2} $x */
function f(array $x): void {
	if ($x['t'] === 1) {
		dumpType($x);
	} else {
		dumpType($x);
	}
}

/** @param object{t: 1}|object{t: 2} $x */
function g(object $x): void {
	if ($x->t === 1) {
		dumpType($x);
	} else {
		dumpType($x);
	}
}
