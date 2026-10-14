<?php

namespace App\Enums;

enum SetupStep: string
{
    case PLATFORM = 'platform';
    case CONNECT = 'connect';
    case ANALYSIS = 'analysis';
    case FEATURES = 'features';
    case THEME = 'theme';
    case BRANDING = 'branding';
    case PREVIEW = 'preview';
    case INTEGRATIONS = 'integrations';
    case PAYMENT = 'payment';
    case BUILD = 'build';

    /**
     * Get 0-based sequence order of the setup step.
     */
    public function order(): int
    {
        return match ($this) {
            self::PLATFORM => 0,
            self::CONNECT => 1,
            self::ANALYSIS => 2,
            self::FEATURES => 3,
            self::THEME => 4,
            self::BRANDING => 5,
            self::PREVIEW => 6,
            self::INTEGRATIONS => 7,
            self::PAYMENT => 8,
            self::BUILD => 9,
        };
    }

    /**
     * Check if a route requiring this step can be accessed from the given current step.
     * Rule: required step order <= current step order + 1.
     */
    public function isAllowedFrom(SetupStep $currentStep): bool
    {
        return $this->order() <= ($currentStep->order() + 1);
    }

    /**
     * Get the next logical setup step.
     */
    public function next(): ?self
    {
        return match ($this) {
            self::PLATFORM => self::CONNECT,
            self::CONNECT => self::ANALYSIS,
            self::ANALYSIS => self::FEATURES,
            self::FEATURES => self::THEME,
            self::THEME => self::BRANDING,
            self::BRANDING => self::PREVIEW,
            self::PREVIEW => self::INTEGRATIONS,
            self::INTEGRATIONS => self::PAYMENT,
            self::PAYMENT => self::BUILD,
            self::BUILD => null,
        };
    }

    /**
     * Get the previous setup step.
     */
    public function previous(): ?self
    {
        return match ($this) {
            self::PLATFORM => null,
            self::CONNECT => self::PLATFORM,
            self::ANALYSIS => self::CONNECT,
            self::FEATURES => self::ANALYSIS,
            self::THEME => self::FEATURES,
            self::BRANDING => self::THEME,
            self::PREVIEW => self::BRANDING,
            self::INTEGRATIONS => self::PREVIEW,
            self::PAYMENT => self::INTEGRATIONS,
            self::BUILD => self::PAYMENT,
        };
    }

    /**
     * Return all steps that come strictly after this step.
     *
     * @return list<self>
     */
    public function subsequentSteps(): array
    {
        $all = self::cases();
        $subsequent = [];

        foreach ($all as $step) {
            if ($step->order() > $this->order()) {
                $subsequent[] = $step;
            }
        }

        return $subsequent;
    }

    /**
     * Safe factory from string with fallback to PLATFORM.
     */
    public static function fromString(?string $value): self
    {
        if ($value === null) {
            return self::PLATFORM;
        }

        return self::tryFrom($value) ?? self::PLATFORM;
    }
}
