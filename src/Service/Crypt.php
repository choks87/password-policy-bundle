<?php
declare(strict_types=1);

namespace Choks\PasswordPolicy\Service;

use Choks\PasswordPolicy\Exception\CryptException;

final class Crypt
{
    public function __construct(
        #[\SensitiveParameter] private readonly string $salt,
        #[\SensitiveParameter] private readonly string $cipherMethod,
    ) {
    }

    public function encrypt(string $password): string
    {
        $key          = \openssl_digest($this->salt, 'SHA256', true);
        $ivLength     = \openssl_cipher_iv_length($this->cipherMethod);

        if (false === $ivLength) {
            throw new CryptException('Crypt Algorithm is not strong.');
        }

        if (false === $key) {
            throw new CryptException('Crypt key failed.');
        }

        /** @var string|false $iv */
        $iv = \openssl_random_pseudo_bytes($ivLength, $isStrong);

        if (false === $isStrong) {
            throw new CryptException('Crypt Algorithm is not strong.');
        }

        if (false === $iv) {
            throw new CryptException('IV failed.');
        }

        return \openssl_encrypt($password, $this->cipherMethod, $key, 0, $iv)."::".bin2hex($iv);
    }

    public function decrypt(string $hashedPassword): string
    {
        [$hashedPassword, $encIv] = explode("::", $hashedPassword);;
        $encKey = openssl_digest($this->salt, 'SHA256', TRUE);

        if (false === $encKey) {
            throw new CryptException('Unable to decrypt. Bad Encryption key.');
        }

        $bin = hex2bin($encIv);

        if (false === $bin) {
            throw new CryptException('Unable to decrypt. Unable to convert to binary.');
        }

        $decrypted = openssl_decrypt($hashedPassword, $this->cipherMethod, $encKey, 0, $bin);

        if (false === $decrypted) {
            throw new CryptException('Unable to decrypt.');
        }

        return $decrypted;
    }
}