#!/bin/bash

# Ensure that we exit on failure, and echo lines as they are executed.
set -ev

# Check to make sure that our build environment is right.
test "$TRAVIS_BRANCH" == "master" || (echo "Skipping docs update - docs only updated for master branch." && exit 0)
test "$TRAVIS_PULL_REQUEST" == "true" || (echo "Skipping docs update -- not done on pull requests." && exit 0)
test "${TRAVIS_PHP_VERSION:0:3}" == "5.6" || (echo "Skipping docs update -- only update for PHP 5.6 build." && exit 0)
test "$TRAVIS_REPO_SLUG" == "lcache/lcache" || (echo "Skipping docs update -- do not build docs for forks." && exit 0)

# Install Sami
composer sami-install

# Build the API documentation
composer api

# Identify the docs bot
git config --global user.email $GITHUB_USER_EMAIL
git config --global user.name "Travis LCache Documentation Bot"

# Check out the gh-pages branch using our Github token (defined at https://travis-ci.org/lcache/lcache/settings)
git clone --quiet --branch=gh-pages https://${GITHUB_TOKEN}@github.com/lcache/lcache $HOME/gh-pages > /dev/null

# Replace the old 'api' folder with the newly-built API documentation
rm -rf $HOME/gh-pages/api
cp -R docs/api $HOME/gh-pages

# Commit any changes to the documentation
cd $HOME/gh-pages
git add -A api
git commit -m "Update API documentation."
git push
