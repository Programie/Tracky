<?php
namespace tracky;

class Pagination
{
    private const FIRST_PAGE = 1;

    public function __construct(
        private readonly int $currentPage,
        private readonly int $totalItems,
        private readonly int $itemsPerPage,
        private readonly int $maxPreviousNextPages = 3
    )
    {
    }

    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    public function getLastPage(): int
    {
        return (int)ceil($this->totalItems / $this->itemsPerPage);
    }

    public function getPreviousPage(): ?int
    {
        if ($this->currentPage > self::FIRST_PAGE) {
            return $this->currentPage - 1;
        }

        return null;
    }

    public function getNextPage(): ?int
    {
        if ($this->currentPage < $this->getLastPage()) {
            return $this->currentPage + 1;
        }

        return null;
    }

    public function getPreviousPages(): array
    {
        $pages = [];

        for ($pageIndex = $this->currentPage - 1; $pageIndex >= self::FIRST_PAGE; $pageIndex--) {
            if (count($pages) >= $this->maxPreviousNextPages) {
                break;
            }

            array_unshift($pages, $pageIndex);
        }

        return $pages;
    }

    public function getNextPages(): array
    {
        $pages = [];
        $lastPage = $this->getLastPage();

        for ($pageIndex = $this->currentPage + 1; $pageIndex <= $lastPage; $pageIndex++) {
            if (count($pages) >= $this->maxPreviousNextPages) {
                break;
            }

            $pages[] = $pageIndex;
        }

        return $pages;
    }
}