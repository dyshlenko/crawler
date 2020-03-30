<?php

namespace Domain;

interface Report
{
    /**
     * @return string
     */
    public function getDefaultFilename(): string;

    /**
     * @return string
     */
    public function getContent(): string;
}