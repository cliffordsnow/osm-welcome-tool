<?php

namespace App\Service;

use App\Entity\Changeset;
use App\Repository\ChangesetRepository;
use DateTimeImmutable;
use SimpleXMLElement;

class ChangesetProvider
{
    public function __construct(
        private ChangesetRepository $repository
    ) {
    }

    public function fromOSMCha(array $array): Changeset
    {
        return new Changeset();
    }

    public function fromOSM(SimpleXMLElement $element): Changeset
    {
        $attributes = $element->attributes();

        $changeset = $this->repository->find((int) $attributes->id);
        if ($changeset === null) {
            $extent = [
                floatval(self::extractTag($element->tag, 'min_lon')),
                floatval(self::extractTag($element->tag, 'min_lat')),
                floatval(self::extractTag($element->tag, 'max_lon')),
                floatval(self::extractTag($element->tag, 'max_lat')),
            ];

            $changeset = new Changeset();
            $changeset->setId((int) $attributes->id);
            $changeset->setCreatedAt(new DateTimeImmutable((string) $attributes->created_at));
            $changeset->setComment(self::extractTag($element->tag, 'comment') ?? '');
            $changeset->setEditor(self::extractTag($element->tag, 'created_by') ?? '');
            $changeset->setLocale(self::extractTag($element->tag, 'locale'));
            $changeset->setChangesCount(intval($attributes->changes_count));
            $changeset->setExtent($extent);
            $changeset->setTags([]);
            // $changeset->setMapper($mapper);
        }

        return $changeset;
    }

    private static function extractTag(SimpleXMLElement $element, string $key): string | null
    {
        /** @var SimpleXMLElement[] */
        $tags = [];
        foreach ($element as $tag) {
            $tags[] = $tag;
        }
        $filter = array_filter($tags, function (SimpleXMLElement $element) use ($key) {
            $attr = $element->attributes();
            return (string) $attr->k === $key;
        });

        if (count($filter) === 0) {
            return null;
        }

        $tag = current($filter);
        $attr = $tag->attributes();

        return (string) $attr->v;
    }
}
