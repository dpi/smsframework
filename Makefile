phpstan-baseline:
	rm baselines/*.neon
	touch baselines/loader.neon
	php -d memory_limit=-1 ./vendor/bin/phpstan analyze --error-format baselinePerIdentifier

phpcs:
	vendor/bin/phpcs

phpcbf:
	vendor/bin/phpcbf

phpstan:
	php -d memory_limit=-1 ./vendor/bin/phpstan analyze

lint: phpcs phpstan

fix: phpcbf

clean:
	rm -rf vendor
	rm -rf app
	rm -rf core
	rm -rf web
	rm composer.lock
