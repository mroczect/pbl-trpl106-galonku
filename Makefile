SHELL         := /bin/bash
.DEFAULT_GOAL := help
MAKEFLAGS     += --no-print-directory

BACKEND       := backend
FRONTEND      := frontend
DATABASE      := database

PHP           ?= php
COMPOSER      ?= composer

ifeq ($(wildcard $(FRONTEND)/pnpm-lock.yaml),$(FRONTEND)/pnpm-lock.yaml)
PKG ?= pnpm
else ifeq ($(wildcard $(FRONTEND)/yarn.lock),$(FRONTEND)/yarn.lock)
PKG ?= yarn
else ifeq ($(wildcard $(FRONTEND)/bun.lockb),$(FRONTEND)/bun.lockb)
PKG ?= bun
else
PKG ?= npm
endif

ESC    := \033
BLUE   := $(ESC)[34m
CYAN   := $(ESC)[36m
GREEN  := $(ESC)[32m
YELLOW := $(ESC)[33m
RED    := $(ESC)[31m
DIM    := $(ESC)[2m
RESET  := $(ESC)[0m

OK   := $(GREEN)✔$(RESET)
WARN := $(YELLOW)⚠$(RESET)
FAIL := $(RED)✘$(RESET)
GO   := $(CYAN)▶$(RESET)
DOT  := $(DIM)·$(RESET)

.PHONY: help
help:
	@printf "\n$(BLUE)Galonku Monorepo$(RESET) $(DIM)— available targets$(RESET)\n\n"
	@printf "$(DIM)Usage:$(RESET) make $(CYAN)<target>$(RESET)\n\n"

	@printf "$(BLUE)Setup$(RESET)\n"
	@printf "  $(GREEN)setup$(RESET)             Full bootstrap (env + deps + database)\n"
	@printf "  $(GREEN)install$(RESET)           Install backend and frontend dependencies\n"
	@printf "  $(GREEN)install-be$(RESET)        Backend composer install\n"
	@printf "  $(GREEN)install-fe$(RESET)        Frontend $(PKG) install\n"
	@printf "  $(GREEN)env$(RESET)               Copy .env templates where missing\n\n"

	@printf "$(BLUE)Run$(RESET)\n"
	@printf "  $(GREEN)serve$(RESET)             Backend dev server  → http://localhost:8000\n"
	@printf "  $(GREEN)dev$(RESET)               Frontend dev server\n\n"

	@printf "$(BLUE)Database$(RESET) $(DIM)(database/db.php)$(RESET)\n"
	@printf "  $(GREEN)db-status$(RESET)         Show migration and seeder status\n"
	@printf "  $(GREEN)db-migrate$(RESET)        Run pending migrations\n"
	@printf "  $(GREEN)db-fresh$(RESET)          Drop all + migrate + seed\n"
	@printf "  $(GREEN)db-seed$(RESET)           Run all seeders\n"
	@printf "  $(GREEN)db-rollback$(RESET)       Rollback last batch\n"
	@printf "  $(GREEN)db-reset$(RESET)          Rollback all + migrate + seed\n"
	@printf "  $(GREEN)db-test-fresh$(RESET)     Fresh testing database (APP_ENV=testing)\n"
	@printf "  $(GREEN)db-drop$(RESET)           Drop database (destructive)\n"
	@printf "  $(GREEN)db-cleanup$(RESET)        Purge JWT blacklist and rate-limit cache\n\n"

	@printf "$(BLUE)Tests$(RESET) $(DIM)(backend/tests)$(RESET)\n"
	@printf "  $(GREEN)test$(RESET)              Run all suites\n"
	@printf "  $(GREEN)test-unit$(RESET)         Unit suite only\n"
	@printf "  $(GREEN)test-feat$(RESET)         Feature suite only\n"
	@printf "  $(GREEN)test-int$(RESET)          Integration suite only\n"
	@printf "  $(GREEN)test-cov$(RESET)          Coverage report → $(BACKEND)/storage/coverage\n\n"

	@printf "$(BLUE)Quality$(RESET)\n"
	@printf "  $(GREEN)lint$(RESET)              PHP syntax check on app, tests, database\n"
	@printf "  $(GREEN)lint-fe$(RESET)           Frontend lint\n"
	@printf "  $(GREEN)check$(RESET)             lint + test (pre-commit)\n\n"

	@printf "$(BLUE)Cleanup$(RESET)\n"
	@printf "  $(GREEN)clean$(RESET)             Remove caches and coverage\n"
	@printf "  $(GREEN)clean-logs$(RESET)        Truncate backend logs\n"
	@printf "  $(GREEN)clean-cache$(RESET)       Remove rate-limit JSON files\n\n"

	@printf "$(BLUE)Git$(RESET)\n"
	@printf "  $(GREEN)status$(RESET)            git status --short\n"
	@printf "  $(GREEN)tree$(RESET)              Repository tree, two levels\n\n"

.PHONY: setup bootstrap install install-be install-fe env

setup: env install db-fresh db-test-fresh
	@printf "\n$(OK) Setup complete. Run $(CYAN)make serve$(RESET) and $(CYAN)make dev$(RESET).\n\n"

bootstrap: setup

install: install-be install-fe
	@printf "$(OK) All dependencies installed\n"

install-be:
	@printf "$(GO) Installing backend dependencies…\n"
	@cd $(BACKEND) && $(COMPOSER) install --no-interaction --prefer-dist
	@printf "$(OK) Backend dependencies ready\n"

install-fe:
	@printf "$(GO) Installing frontend dependencies with $(PKG)…\n"
	@cd $(FRONTEND) && $(PKG) install
	@printf "$(OK) Frontend dependencies ready\n"

env:
	@created=0; \
	for pair in ".env:.env.example" ".env.testing:.env.testing.example"; do \
		dst="$(BACKEND)/$${pair%%:*}"; \
		src="$(BACKEND)/$${pair##*:}"; \
		if [ -f "$$dst" ]; then \
			printf "$(DOT) $$dst already exists\n"; \
			continue; \
		fi; \
		if [ ! -f "$$src" ]; then \
			printf "$(WARN) Template $$src missing — skipped\n"; \
			continue; \
		fi; \
		cp "$$src" "$$dst"; \
		printf "$(OK) Created $$dst\n"; \
		created=1; \
	done; \
	if [ "$$created" = "1" ]; then \
		printf "\n$(WARN) Generate JWT_SECRET: $(CYAN)php -r \"echo bin2hex(random_bytes(32));\"$(RESET)\n"; \
	fi

.PHONY: serve be-serve dev fe-dev

serve be-serve:
	@printf "$(GO) Backend → http://localhost:8000\n"
	@cd $(BACKEND) && $(COMPOSER) serve

dev fe-dev:
	@printf "$(GO) Frontend dev server…\n"
	@cd $(FRONTEND) && $(PKG) run dev

.PHONY: db-status db-migrate db-fresh db-seed db-rollback db-reset db-test-fresh db-drop db-cleanup

db-status:
	@$(PHP) $(DATABASE)/db.php status

db-migrate:
	@$(PHP) $(DATABASE)/db.php migrate

db-fresh:
	@$(PHP) $(DATABASE)/db.php fresh --seed

db-seed:
	@$(PHP) $(DATABASE)/db.php seed

db-rollback:
	@$(PHP) $(DATABASE)/db.php rollback

db-reset:
	@$(PHP) $(DATABASE)/db.php reset --seed

db-test-fresh:
	@APP_ENV=testing $(PHP) $(DATABASE)/db.php fresh --seed

db-drop:
	@printf "$(WARN) Dropping database…\n"
	@$(PHP) $(DATABASE)/db.php drop --force

db-cleanup:
	@$(PHP) $(DATABASE)/cleanup.php

.PHONY: test test-unit test-feat test-int test-cov

test:
	@cd $(BACKEND) && $(COMPOSER) test

test-unit:
	@cd $(BACKEND) && $(COMPOSER) test:unit

test-feat:
	@cd $(BACKEND) && $(COMPOSER) test:feat

test-int:
	@cd $(BACKEND) && $(COMPOSER) test:int

test-cov:
	@cd $(BACKEND) && $(COMPOSER) test:cov
	@printf "$(OK) Coverage → $(BACKEND)/storage/coverage/index.html\n"

.PHONY: lint lint-fe check

lint:
	@printf "$(GO) PHP syntax check…\n"
	@fail=0; \
	for f in $$(find $(BACKEND)/app $(BACKEND)/tests $(DATABASE) -name '*.php' -not -path '*/vendor/*' 2>/dev/null); do \
		out=$$($(PHP) -l "$$f" 2>&1) || { printf '%s\n' "$$out"; fail=1; }; \
	done; \
	if [ "$$fail" -eq 0 ]; then \
		printf "$(OK) All PHP files OK\n"; \
	else \
		printf "$(FAIL) PHP syntax errors found\n"; \
		exit 1; \
	fi

lint-fe:
	@cd $(FRONTEND) && $(PKG) run lint

check: lint test
	@printf "$(OK) Pre-commit checks passed\n"

.PHONY: clean clean-logs clean-cache

clean: clean-logs clean-cache
	@rm -rf $(BACKEND)/.phpunit.cache $(BACKEND)/storage/coverage
	@printf "$(OK) Caches and coverage removed\n"

clean-logs:
	@rm -f $(BACKEND)/storage/logs/*.log
	@printf "$(OK) Backend logs cleared\n"

clean-cache:
	@rm -f $(BACKEND)/storage/cache/rl_*.json
	@printf "$(OK) Rate-limit cache cleared\n"

.PHONY: status tree

status:
	@git status --short

tree:
	@if command -v tree >/dev/null 2>&1; then \
		tree -L 2 -I 'vendor|node_modules|.git|storage/cache|storage/logs|tmp'; \
	else \
		find . -maxdepth 2 -not -path '*/\.*' -not -path '*/vendor/*' -not -path '*/node_modules/*' | sort; \
	fi
