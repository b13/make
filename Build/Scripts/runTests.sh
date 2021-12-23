#!/usr/bin/env bash

#
# TYPO3 core test runner based on docker and docker-compose.
#

# Function to write a .env file in Build/testing-docker
# This is read by docker-compose and vars defined here are
# used in Build/testing-docker/docker-compose.yml
setUpDockerComposeDotEnv() {
    # Delete possibly existing local .env file if exists
    [ -e .env ] && rm .env
    # Set up a new .env file for docker-compose
    {
        echo "COMPOSE_PROJECT_NAME=local"
        # To prevent access rights of files created by the testing, the docker image later
        # runs with the same user that is currently executing the script. docker-compose can't
        # use $UID directly itself since it is a shell variable and not an env variable, so
        # we have to set it explicitly here.
        echo "HOST_UID=`id -u`"
        # Your local home directory for composer and npm caching
        echo "HOST_HOME=${HOME}"
        echo "CORE_ROOT=${CORE_ROOT}"
        # Your local user
        echo "HOST_USER=${USER}"
        echo "TEST_FILE=${TEST_FILE}"
        echo "PHP_XDEBUG_ON=${PHP_XDEBUG_ON}"
        echo "PHP_XDEBUG_PORT=${PHP_XDEBUG_PORT}"
        echo "DOCKER_PHP_IMAGE=${DOCKER_PHP_IMAGE}"
        echo "TYPO3=${TYPO3}"
        echo "PHP_VERSION=${PHP_VERSION}"
        echo "EXTRA_TEST_OPTIONS=${EXTRA_TEST_OPTIONS}"
        echo "SCRIPT_VERBOSE=${SCRIPT_VERBOSE}"
        echo "CGLCHECK_DRY_RUN=${CGLCHECK_DRY_RUN}"
    } > .env
}

# Load help text into $HELP
read -r -d '' HELP <<EOF
make test runner. Execute unit test suite and some other details.
Also used by github actions for test execution.

Successfully tested with docker version 18.06.1-ce and docker-compose 1.21.2.

Usage: $0 [options] [file]

No arguments: Run all unit tests with PHP 7.4

Options:
    -s <...>
        Specifies which test suite to run
            - acceptance: backend acceptance tests
            - cgl: cgl test and fix all php files
            - composerUpdate: "composer update", handy if host has no PHP
            - composerValidate: "composer validate"
            - functional: functional tests
            - lint: PHP linting
            - phpstan: phpstan analyze
            - unit (default): PHP unit tests

    -t <10|11>
        Only with -s composerUpdate|acceptance|functional
        TYPO3 core major version the extension is embedded in for testing.

    -d <mariadb|postgres|sqlite>
        Only with -s functional
        Specifies on which DBMS tests are performed
            - mariadb (default): use mariadb
            - postgres: use postgres
            - sqlite: use sqlite

    -p <7.2|7.3|7.4|8.0|8.1>
        Specifies the PHP minor version to be used
            - 7.4 (default): use PHP 7.4

    -e "<phpunit or codeception options>"
        Only with -s acceptance|functional|unit
        Additional options to send to phpunit (unit & functional tests) or codeception (acceptance
        tests). For phpunit, options starting with "--" must be added after options starting with "-".
        Example -e "-v --filter canRetrieveValueWithGP" to enable verbose output AND filter tests
        named "canRetrieveValueWithGP"

    -x
        Only with -s functional|unit|acceptance
        Send information to host instance for test or system under test break points. This is especially
        useful if a local PhpStorm instance is listening on default xdebug port 9003. A different port
        can be selected with -y

    -y <port>
        Send xdebug information to a different port than default 9003 if an IDE like PhpStorm
        is not listening on default port.

    -n
        Only with -s cgl
        Activate dry-run in CGL check that does not actively change files and only prints broken ones.

    -u
        Update existing typo3/core-testing-*:latest docker images. Maintenance call to docker pull latest
        versions of the main php images. The images are updated once in a while and only the youngest
        ones are supported by core testing. Use this if weird test errors occur. Also removes obsolete
        image versions of typo3/core-testing-*.

    -v
        Enable verbose script output. Shows variables and docker commands.

    -h
        Show this help.

Examples:
    # Run unit tests using PHP 7.4
    ./Build/Scripts/runTests.sh

    # Run unit tests using PHP 7.3
    ./Build/Scripts/runTests.sh -p 7.3
EOF

# Test if docker-compose exists, else exit out with error
if ! type "docker-compose" > /dev/null; then
  echo "This script relies on docker and docker-compose. Please install" >&2
  exit 1
fi

# docker-compose v2 is enabled by docker for mac as experimental feature without
# asking the user. v2 is currently broken. Detect the version and error out.
DOCKER_COMPOSE_VERSION=$(docker-compose version --short)
DOCKER_COMPOSE_MAJOR=$(echo "$DOCKER_COMPOSE_VERSION" | cut -d'.' -f1 | tr -d 'v')
if [ "$DOCKER_COMPOSE_MAJOR" -gt "1" ]; then
    echo "docker-compose $DOCKER_COMPOSE_VERSION is currently broken and not supported by runTests.sh."
    echo "If you are running Docker Desktop for MacOS/Windows disable 'Use Docker Compose V2 release candidate' (Settings > Experimental Features)"
    exit 1
fi

# Go to the directory this script is located, so everything else is relative
# to this dir, no matter from where this script is called.
THIS_SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null && pwd )"
cd "$THIS_SCRIPT_DIR" || exit 1

# Go to directory that contains the local docker-compose.yml file
cd ../testing-docker || exit 1

# Set core root path by checking whether realpath exists
if ! command -v realpath &> /dev/null; then
  echo "Consider installing realpath for properly resolving symlinks" >&2
  CORE_ROOT="${PWD}/../../"
else
  CORE_ROOT=$(realpath "${PWD}/../../")
fi

# Option defaults
TEST_SUITE="unit"
DBMS="mariadb"
PHP_VERSION="7.4"
PHP_XDEBUG_ON=0
PHP_XDEBUG_PORT=9003
EXTRA_TEST_OPTIONS=""
SCRIPT_VERBOSE=0
CGLCHECK_DRY_RUN=""
TYPO3="10"

# Option parsing
# Reset in case getopts has been used previously in the shell
OPTIND=1
# Array for invalid options
INVALID_OPTIONS=();
# Simple option parsing based on getopts (! not getopt)
while getopts ":s:d:p:e:t:xy:nhuv" OPT; do
    case ${OPT} in
        s)
            TEST_SUITE=${OPTARG}
            ;;
        d)
            DBMS=${OPTARG}
            ;;
        p)
            PHP_VERSION=${OPTARG}
            if ! [[ ${PHP_VERSION} =~ ^(7.2|7.3|7.4|8.0|8.1)$ ]]; then
                INVALID_OPTIONS+=("${OPTARG}")
            fi
            ;;
        t)
            TYPO3=${OPTARG}
            ;;
        e)
            EXTRA_TEST_OPTIONS=${OPTARG}
            ;;
        x)
            PHP_XDEBUG_ON=1
            ;;
        y)
            PHP_XDEBUG_PORT=${OPTARG}
            ;;
        h)
            echo "${HELP}"
            exit 0
            ;;
        n)
            CGLCHECK_DRY_RUN="-n"
            ;;
        u)
            TEST_SUITE=update
            ;;
        v)
            SCRIPT_VERBOSE=1
            ;;
        \?)
            INVALID_OPTIONS+=("${OPTARG}")
            ;;
        :)
            INVALID_OPTIONS+=("${OPTARG}")
            ;;
    esac
done

# Exit on invalid options
if [ ${#INVALID_OPTIONS[@]} -ne 0 ]; then
    echo "Invalid option(s):" >&2
    for I in "${INVALID_OPTIONS[@]}"; do
        echo "-"${I} >&2
    done
    echo >&2
    echo "${HELP}" >&2
    exit 1
fi

# Move "7.4" to "php74", the latter is the docker container name
DOCKER_PHP_IMAGE=$(echo "php${PHP_VERSION}" | sed -e 's/\.//')

# Set $1 to first mass argument, this is the optional test file or test directory to execute
shift $((OPTIND - 1))
TEST_FILE=${1}
if [ -n "${1}" ]; then
    TEST_FILE="Web/typo3conf/ext/make/${1}"
fi

if [ ${SCRIPT_VERBOSE} -eq 1 ]; then
    set -x
fi

# Suite execution
case ${TEST_SUITE} in
    acceptance)
        setUpDockerComposeDotEnv
        docker-compose run acceptance_backend_mariadb10
        SUITE_EXIT_CODE=$?
        docker-compose down
        ;;
    cgl)
        # Active dry-run for cgl needs not "-n" but specific options
        if [ -n "${CGLCHECK_DRY_RUN}" ]; then
            CGLCHECK_DRY_RUN="--dry-run --diff"
        fi
        setUpDockerComposeDotEnv
        docker-compose run cgl
        SUITE_EXIT_CODE=$?
        docker-compose down
        ;;
    composerUpdate)
        setUpDockerComposeDotEnv
        docker-compose run composer_update
        SUITE_EXIT_CODE=$?
        docker-compose down
        ;;
    composerValidate)
        setUpDockerComposeDotEnv
        docker-compose run composer_validate
        SUITE_EXIT_CODE=$?
        docker-compose down
        ;;
    functional)
        setUpDockerComposeDotEnv
        case ${DBMS} in
            mariadb)
                docker-compose run functional_mariadb10
                SUITE_EXIT_CODE=$?
                ;;
            postgres)
                docker-compose run functional_postgres10
                SUITE_EXIT_CODE=$?
                ;;
            sqlite)
                mkdir -p ${CORE_ROOT}/.Build/Web/typo3temp/var/tests/functional-sqlite-dbs/
                docker-compose run functional_sqlite
                SUITE_EXIT_CODE=$?
                ;;
            *)
                echo "Invalid -d option argument ${DBMS}" >&2
                echo >&2
                echo "${HELP}" >&2
                exit 1
        esac
        docker-compose down
        ;;
    lint)
        setUpDockerComposeDotEnv
        docker-compose run lint
        SUITE_EXIT_CODE=$?
        docker-compose down
        ;;
    phpstan)
        setUpDockerComposeDotEnv
        docker-compose run phpstan
        SUITE_EXIT_CODE=$?
        docker-compose down
        ;;
    unit)
        setUpDockerComposeDotEnv
        docker-compose run unit
        SUITE_EXIT_CODE=$?
        docker-compose down
        ;;
    update)
        # pull typo3/core-testing-*:latest versions of those ones that exist locally
        docker images typo3/core-testing-*:latest --format "{{.Repository}}:latest" | xargs -I {} docker pull {}
        # remove "dangling" typo3/core-testing-* images (those tagged as <none>)
        docker images typo3/core-testing-* --filter "dangling=true" --format "{{.ID}}" | xargs -I {} docker rmi {}
        ;;
    *)
        echo "Invalid -s option argument ${TEST_SUITE}" >&2
        echo >&2
        echo "${HELP}" >&2
        exit 1
esac

exit $SUITE_EXIT_CODE
