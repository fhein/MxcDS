<?php

namespace MxcDropship\Interfaces;

interface ArticleRegistryInterface
{
    public function register(int $detailId, string $productNumber, bool $active, int $delivery);
    public function unregister(int $detailId);
    public function getSettings(int $detailId);
}