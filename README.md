![CI](https://github.com/choks87/password-policy-bundle/actions/workflows/ci.yml/badge.svg)
# What is this?
Password Policy is a Symfony Bundle where you can validate user passwords against policy.

# Pre-Requirements
- PHP: >=8.1
- Openssl PHP Extension
- Symfony 6 or 7

# Installation
Install via composer:
```bash
composer require choks/password-policy-bundle
```

Add to your bundles:
```php
Choks\PasswordPolicy\PasswordPolicy::class => ['all' => true],
```

If you are using doctrine, when generating schema, a table for storing Password History will be installed
automatically. If don't, you will need to [create it manually](#table-for-storing-history). Currently, only dbal storage is supported.

# Usage
Before we dig, let's explain how it works. First, you can use validation against policy on any object as long
as that object implements `Choks\PasswordPolicy\Contract\PasswordPolicySubjectInterface`, which will require
to implement `getIdentifier()` so we can distinguish owner of password when saving into Password History and 
`getPlainPassword()` so we can compare (why plain password?)

Basically there are two ways of validating:
- Manually, by calling service methods
- Automatically, by putting `#[Choks\PasswordPolicy\Atrribute\Listen]` on Doctrine Entity

And two ways of specifying the policy in your application:
- Via bundle configuration (or)
- Via your own Policy Provider

## Defining policy
### Via Configuration

```yaml
password_policy:
  policy:
    character:
      min_length: 8 # Minimum password length, leave null if you don't want to use
      numbers: 1 # At least how many numbers there? Leave null if you don't want to use
      lowercase: 1 # At least how many lowercase characters there? Leave null if you don't want to use
      uppercase: 1 # At least how many uppercase characters there?Leave null if you don't want to use
      special: 1 # At least how many special characters there? Leave null if you don't want to use
    # Password history policy is used when you want your passwords to be validated against previous passwords.
    # By default, History Policy is not used.
    history:
      # Provided password should not be used in 10 previous passwords. Leave null if you don't want to use
      not_used_in_past_n_passwords: 10
      # Period for which we should look in the past. Leave null if you don't want to use
      period:
        unit: 'month' # Possible values are 'day', 'week', 'year' (Enum: Choks\PasswordPolicy\Enum\PeriodUnit)
        value: 1
```
That's it, your configuration is set

### Via your own Policy Provider

Here is an example of your custom Policy Provider.
```php
use Choks\PasswordPolicy\Contract\PolicyInterface;
use Choks\PasswordPolicy\Contract\PolicyProviderInterface;
use Choks\PasswordPolicy\Model\CharacterPolicy;
use Choks\PasswordPolicy\Model\HistoryPolicy;
use Choks\PasswordPolicy\Model\Policy;

final class MyCustomPolicyProvider implements PolicyProviderInterface
{
    public function getPolicy(UserInterface $user): PolicyInterface
    {
        // Assuming that you have your own way of storing Policy configuration, for example db.
        $policyData = $this->entityManager->getRepository()->yourOwnWayOfFetchingData();

        return new Policy(
            new CharacterPolicy(
                $policyData['min_length'],
                $policyData['numbers'],
                $policyData['lowercase'],
                $policyData['uppercase'],
                $policyData['special'],
            )
            new HistoryPolicy(
                $policyData['not_used_in_past_n_passwords'],
                $policyData['period_unit'],
                $policyData['period_value'],
            )
        );
    }
}
```
Next step is to register this as service and then, put it to bundle config:
```yaml
password_policy:
  policy_provider: MyCustomPolicyProvider::class # You can put your own provider here
```
Whenever you try to validate manually or automatically, this provider will be called to get Policy to use.

## Checking Policy and manipulating Password History

### Manually

#### Checking against policy
You can get or inject Checker Service via ID `password_policy.checker` or `Choks\PasswordPolicy\Contract\PolicyCheckerInterface`.
Let's say you are validating `$user` (Remember, $user has to implement `PasswordPolicySubjectInterface`)
```php
$checker = $this->getContainer()->get('password_policy.checker')
$violations = $checker->validate($user);

if ($violations->hasErrors()) {
    // Do own stuff, you have violations to check error messages.
}
```

#### Adding to password history (if you use it)
You can get or inject Password History service via ID `password_policy.history` or `Choks\PasswordPolicy\Contract\PasswordHistoryInterface`
```php
$history = $this->getContainer()->get('password_policy.history')
$history->add($user); // This will write password into password history.
# ...
# also:

$history->clear(); // This will clear all passwords in history, for all users.
$history->remove($user); // This will clear all passwords in history, for specific User.
```

### Automatic checking
Although, I would encourage to manually control flow of checking, it's much easier to let bundle do that for you.
By adding `#[Listen]` attribute, you are expecting bundle to automatically:
- When User is being inserted or update (when flushed):
  - Validate password retrieved by `getPlainPassword()` against current policy, 
  - Add password to history (using crypt, see complete config reference below) if history policy is used.
- When User is being removed (when flushed):
  - Remove passwords for user in history.

```php
use Choks\PasswordPolicy\Contract\PasswordPolicySubjectInterface;
use Doctrine\ORM\Mapping as ORM;
use Choks\PasswordPolicy\Atrribute\Listen;

#[Listen]
#[ORM\Entity]
class User implements PasswordPolicySubjectInterface
{
    #[ORM\Id]
    #[ORM\Column]
    public int $id;

    #[\SensitiveParameter]
    public ?string $plainPassword = null;

    public function __construct(int $id, string $plainPassword = null)
    {
        $this->id            = $id;
        $this->plainPassword = $plainPassword;
    }

    public function getIdentifier(): string
    {
        return (string)$this->id;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }
}
```
At the momnent of saving entity, if validation fails, the `Choks\PasswordPolicy\Exception\PolicyCheckException` will be
thrown. If you catch it, you can examine violations via `getViolations()`,

## Clearing all password history
In some cases, you want to clear all passwords from history.
Probably after update of this bundle, or when you change policy or so.
You can do that by executing a command:
```bash
bin/console password-policy:clear:history
```

## Why plain password? Is it safe?
When using Symfony's `password_hashers` algo, could be and it is usually non-deterministic.
What it means is that every hash for same plain password is different. Also, and usually, those hashing algorithms are
one direction only, means that it cannot be decrypted/un-hashed.

Hahser also does not give us ability to compare Users plain password with some hashed one, it can only verify
hashed user password using `UserPasswordHasherInterface` on User, against plain one.

We need to store encrypted password in history, and in order to do that, bundle is using its own crypt algo (That's
why you have `cipher_method` in configuration. You can always choose different one.). When we compare user plain password
with password in history, we are decrypting those and compare. 

How you will deliver plain password via `getPlainPassword()` it's up to you, but I encourage you not to persist it, and
if can use `eraseCredentials()` to unset it.

Note: If `getPlainPassword()` return NULL, every password policy operation will be skipped.

# Configuration Reference
```yaml
password_policy:
  enabled: true # You can turn off this bundle
  policy_provider: ConfigurationPolicyProvider::class # You can put your own provider here
  special_chars: "\"'!@#$%^&*()_+=-`~.,;:<>[]{}\\|" # Which characters are considered special chars
  trim: true # Should we trim given password?
  salt: '%env(APP_SECRET)%' # Salt used when encrypting passwords
  cipher_method: aes-128-ctr # Check https://www.php.net/manual/en/function.openssl-get-cipher-methods.php

  # This policy is what would be used in your application as policy, if you don't specify your own provider
  policy:
    character:
      min_length: 8 # Minimum password length (default is null)
      numbers: 1 # At least how many numbers there? (default is null)
      lowercase: 1 # At least how many lowercase characters there? (default is null)
      uppercase: 1 # At least how many uppercase characters there? (default is null)
      special: 1 # At least how many special characters there? (default is null)
    # Password history policy is used when you want your passwords to be validated against previous passwords.
    # By default, History Policy is not used.
    history:
      not_used_in_past_n_passwords: 10 # Provided password should not be used in 10 previous passwords.
      period: # Period for which we should look in the past. 
        unit: 'month' # Possible values are 'day', 'week', 'year' (default is null) 
        value: 1  (default is null)

  storage:
    dbal:
        table: 'password_history' # Name of the table where historic passwords should be stored.
        connection: 'default' # Doctrine DBAL connection name.
```
Note: `not_used_in_past_n_passwords` and `period` could be used combined or independent (one set, other not). But in o
order to use period, both unit and value must be set.
### Table for storing History
If you are not using doctrine generate schema and in some case your table didn't get created, 
you can create it manually by this DDL:
```mysql
CREATE TABLE password_history
(
    subject_id    VARCHAR(64)  NOT NULL,
    password      VARCHAR(128) NOT NULL,
    created_at    DATETIME     NOT NULL COMMENT '(DC2Type:datetime_immutable)'
)
    COLLATE = utf8mb4_unicode_ci;

CREATE INDEX IDX_F3521448B8E8428
    ON password_history (created_at);

CREATE INDEX IDX_F352144A76ED395
    ON password_history (subject_id);
```

# What is planned to be done in future (not promised)?
- Another part of policy, for password policy expiration
- Custom Policy provider per entity, defined via #[Listen]

# Contribute
Feel free to contribute, at any time. Please provide new tests or tests changed.
Also, if you find some bug, open an issue and will try to fix it as soon as possible.

# Todo
- [ ] Customization of messages / Translation
- [ ] Garbage collection for passwords in history (FILO, per User)
- [ ] Support schema update without Doctrine ORM, without PostSchema listener
