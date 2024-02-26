<?php
declare(strict_types=1);

namespace Choks\PasswordPolicy\Criteria;

use Choks\PasswordPolicy\Contract\PasswordPolicySubjectInterface;
use Choks\PasswordPolicy\Enum\Order;

final class SearchCriteria
{
    private ?PasswordPolicySubjectInterface $subject   = null;
    private ?\DateTimeImmutable             $startDate = null;
    private ?\DateTimeImmutable             $endDate   = null;
    private ?Order                          $order     = null;
    private ?int                            $limit     = null;

    public function getLimit(): ?int
    {
        return $this->limit;
    }

    public function setLimit(?int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    public function getStartDate(): ?\DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTimeImmutable $startDate): self
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeImmutable $endDate): self
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function setOrder(?Order $order): self
    {
        $this->order = $order;

        return $this;
    }

    public function getSubject(): ?PasswordPolicySubjectInterface
    {
        return $this->subject;
    }

    public function setSubject(?PasswordPolicySubjectInterface $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    public function __toString(): string
    {
        return \sprintf(
            'Subject: %s, Start Date: %s, End Date: %s, Order: %s, Limit: %s',
            $this->subject?->getIdentifier() ?? 'N\A',
            $this->startDate?->format(DATE_ATOM) ?? 'N\A',
            $this->endDate?->format(DATE_ATOM) ?? 'N\A',
            $this->order?->value ?? 'N\A',
            $this->limit ?? 'N\A',
        );
    }
}