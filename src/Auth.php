<?php

declare(strict_types=1);

namespace Auth
{
    use DB;
    use Jwt;
    use Crypt;
    use Validator;

    /**
     * Gets logged in user data
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Auth\user(); // user object
     * Auth\user()->email;
     * Auth\user()->name;
     * Auth\user(); // null if user not logged in
     * ```
     */
    function user(): ?object
    {
        global $mikro;

        if (! check()) {
            return null;
        }

        return $mikro[USER];
    }

    /**
     * Checks user abilities
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Auth\can('posts.show'); // bool
     * Auth\can('posts.show|posts.create');
     * Auth\can(['posts.show', 'posts.create']);
     * ```
     */
    function can(string|array $abilities): bool
    {
        if (! check()) {
            return false;
        }

        if (\is_string($abilities)) {
            if (\strpos($abilities, '&') !== false) {
                $abilities = \explode('&', $abilities);

                foreach ($abilities as $ability) {
                    if (! \in_array($ability, abilities())) {
                        return false;
                    }
                }

                return true;
            }

            $abilities = \explode('|', $abilities);
        }

        if (\in_array('*', abilities())) {
            return true;
        }

        foreach ($abilities as $ability) {
            if (! \in_array($ability, abilities())) {
                return false;
            }
        }

        return true;
    }

    /**
     * Gets user abilities if logged in
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Auth\abilities(); // ['posts.show', 'posts.create']
     * ```
     */
    function abilities(): array
    {
        if (! check()) {
            return [];
        }

        return (array) \json_decode((string) user()->abilities, true);
    }

    /**
     * Specifies the user type when the parameter is given. This method acts as a tenancy.
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Auth\type(); // 'default'
     * Auth\type('admin');
     * ```
     */
    function type(?string $type = null): string
    {
        global $mikro;

        if ($type === null) {
            return $mikro[TYPE] ?? 'default';
        }

        return $mikro[TYPE] = $type;
    }

    /**
     * Creates a session based on user information.
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Auth\login('email@foo.com', 'password'); // creates session and sets cookie
     * Auth\login('email@foo.com', 'password', false); // creates only session
     * ```
     */
    function login(string $email, string $password, bool $create = true): bool
    {
        global $mikro;

        if (check()) {
            return true;
        }

        $user = DB\table($mikro[TABLE] ?? 'users')
            ->find('where email=:email and type=:type', [
                'email' => $email,
                'type' => type()
            ]);

        if (! $user) {
            return false;
        }

        if (\password_verify($password, $user->password)) {
            $mikro[USER] = $user;

            if ($create) {
                create($user);
            }

            return true;
        }

        return false;
    }

    /**
     * Creates a Jwt token for the user and sets a cookie
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Auth\create(DB\table('users')->find(1));
     * ```
     */
    function create(object $user): void
    {
        global $mikro;

        $expiration = \time() + (int) ($mikro[EXPIRATION] ?? 86400);
        $jwt = Jwt\create($user->email, $expiration, ($mikro[ISSUER] ?? 'mikro_auth'));

        \setcookie(($mikro[ISSUER] ?? 'mikro_auth'), $jwt, time() + $expiration, '/');
    }

    /**
     * Ends the user session
     *
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Auth\logout();
     * ```
     */
    function logout(): void
    {
        global $mikro;

        unset($mikro[USER]);
        \setcookie(($mikro[ISSUER] ?? 'mikro_auth'), '', -1, '/');
    }

    /**
     * Checks whether the user's session is open
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Auth\check(); // bool
     *
     * if (Auth\check()) {
     *     // logged in
     * }
     *
     * Auth\type('admin');
     * Auth\check(); // checks type for 'admin'
     *
     * // or
     *
     * Auth\check('admin'); // checks type for 'admin'
     * ```
     */
    function check(?string $type = null): bool
    {
        global $mikro;

        if (\array_key_exists(USER, $mikro)) {
            return true;
        }

        $token = $_COOKIE[$mikro[ISSUER] ?? 'mikro_auth'] ?? null;

        if ($token === null || Jwt\expired($token)) {
            return false;
        }

        $payload = Jwt\decode($token);
        $user = DB\table($mikro[TABLE] ?? 'users')
            ->find('where email=:email and type=:type', [
                'email' => $payload->uid ?? '',
                'type' => $type ?? type()
            ]);

        if ($user) {
            $mikro[USER] = $user;

            return true;
        }

        return false;
    }

    /**
     * Create new user
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Auth\register([
     *     'email' => 'foo@bar.baz'.
     *     'password' => 'secret',
     *     'name' => 'foo',
     * ]); // bool true if ok
     * ```
     */
    function register(array $data = []): bool
    {
        global $mikro;

        $table = $mikro[TABLE] ?? 'users';
        $email = 'filter_var:' . \FILTER_VALIDATE_EMAIL;
        $len = fn($value) => \mb_strlen((string) $value) <= 255;
        $valid = Validator\is_validated_all($data, [
            'name' => ['isset', '!empty', $len],
            'email' => ['isset', '!empty', $len, $email],
            'password' => ['isset', '!empty', $len],
        ]);

        if (! $valid) {
            return false;
        }

        $data['password'] = Crypt\bcrypt($data['password']);
        $data['type'] = $data['type'] ?? type();

        if (
            DB\table($table)->find(
                'where email=? and type=?',
                [$data['email'], $data['type'] ?? type()]
            )
        ) {
            return false;
        }

        DB\table($table)->insert($data);

        return true;
    }

    /**
     * Create users table on database (mysql and sqlite compatible)
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Auth\migrate();
     * ```
     */
    function migrate(): mixed
    {
        global $mikro;

        $table = $mikro[TABLE] ?? 'users';

        return DB\exec(<<<SQL
            CREATE TABLE IF NOT EXISTS `{$table}` (
                id INTEGER PRIMARY KEY /*!40101 AUTO_INCREMENT */,
                email VARCHAR(100) NOT NULL,
                password VARCHAR(50) NOT NULL,
                name VARCHAR(50) NOT NULL,
                type VARCHAR(20) DEFAULT 'default',
                abilities JSON,
                data JSON
            );
        SQL);
    }

    /**
     * Auth table constant
     *
     * {@inheritDoc} **Example:**
     * ```php
     * $mikro[Auth\TABLE] = 'another_users_table_name';
     * ```
     */
    const TABLE = 'Auth\TABLE';

    /**
     * Auth expiration constant
     *
     * {@inheritDoc} **Example:**
     * ```php
     * $mikro[Auth\EXPIRATION] = 86400; // 1 day
     * ```
     */
    const EXPIRATION = 'Auth\EXPIRATION';

    /**
     * Auth issuer constant
     *
     * {@inheritDoc} **Example:**
     * ```php
     * $mikro[Auth\ISSUER] = 'title';
     * ```
     */
    const ISSUER = 'Auth\ISSUER';

    /**
     * @internal
     */
    const USER = 'Auth\USER';

    /**
     * @internal
     */
    const TYPE = 'Auth\TYPE';
};
