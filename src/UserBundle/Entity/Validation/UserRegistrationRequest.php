<?php

namespace UserBundle\Entity\Validation;

use Symfony\Component\Validator\Constraints as Assert;
use Rollerworks\Component\PasswordStrength\Validator\Constraints\PasswordStrength;

class UserRegistrationRequest
{
    /**
     * @Assert\NotBlank()
     * @Assert\Email(
     *     message = "The email '{{ value }}' is not a valid email.",
     *     checkMX = true
     * )
     */
    public $email;

    /**
     * @Assert\NotBlank()
     * @PasswordStrength(minLength=7, minStrength=2)
     */
    public $password;

    /**
     * @Assert\NotBlank()
     */
    public $title;

    /**
     * @Assert\NotBlank()
     */
    public $firstName;

    /**
     * @Assert\NotBlank()
     */
    public $lastName;


    /**
     * @Assert\NotBlank()
     */
    public $countryId;

    /**
     *
     * @Assert\Expression(
     *     "this.challengeGenerated == value",
     *     message="Challenge not passed"
     * )
     */
    public $challenge;


    /**
     * @Assert\NotBlank()
     */
    public $challengeGenerated;
}