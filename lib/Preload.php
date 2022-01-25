<?php

/**
 * This class preloads and stores various data that we know we will need
 * later. We do this in order to reduce the number of SQL queries. For
 * example, if we need to display 10 trees with 100 meanings, each of which
 * have relations, tags and sources, it is much more efficient to preload them
 * in one SQL query per object type.
 *
 * You can also call this code to load data for a single object. You just
 * won't get the benefit of loading data in bulk.
 *
 * I'm not sure if this code belongs in a separate class or dispersed across
 * various model classes.
 */

class Preload {

  /**
   * Given an array of object IDs to load, keep only unique IDs that we don't
   * already have.
   */
  static function filterIds(array &$ids, array &$existingMap) {
    $results = [];
    foreach ($ids as $id) {
      if (!isset($existingMap[$id])) {
        $results[] = $id;
      }
    }
    return array_unique($results);
  }

  /************************** an entry's trees **************************/

  /**
   * Maps entry IDs to lists of trees.
   */
  private static array $entryTrees = [];

  /**
   * Loads trees for all entries with the given IDs.
   */
  static function loadEntryTrees(array $entryIds) {
    $entryIds = self::filterIds($entryIds, self::$entryTrees);

    if (empty($entryIds)) {
      return;
    }

    $trees = Model::factory('Tree')
      ->table_alias('t')
      ->select('t.*')
      ->select('te.entryId')
      ->join('TreeEntry', [ 't.id', '=', 'te.treeId' ], 'te')
      ->where_in('te.entryId', $entryIds ?: [ 0 ])
      ->order_by_asc('te.treeRank')
      ->find_many();

    foreach ($trees as $t) {
      self::$entryTrees[$t->entryId][] = $t;
      unset($t->entryId);
    }

    $treeIds = Util::objectProperty($trees, 'id');
    self::loadTreeMeanings($treeIds);
    self::loadTreeTags($treeIds);
  }

  static function getEntryTrees($entryId) {
    self::loadEntryTrees([$entryId]);
    return self::$entryTrees[$entryId];
  }

  /************************* an entry's lexemes *************************/

  /**
   * Maps entry IDs to pairs of lists ($mainLexemes, $variants).
   */
  private static array $entryLexemes = [];

  /**
   * Populates the main lexemes and variants for multiple entries at once.
   */
  static function loadEntryLexemes(array $entryIds) {
    $entryIds = self::filterIds($entryIds, self::$entryLexemes);

    if (empty($entryIds)) {
      return;
    }

    // initialize to pair of empty lists
    foreach ($entryIds as $entryId) {
      self::$entryLexemes[$entryId] = [ [], [] ];
    }

    $lexemes = Model::factory('Lexeme')
      ->table_alias('l')
      ->select('l.*')
      ->select('el.entryId')
      ->select('el.main')
      ->join('EntryLexeme', [ 'l.id', '=', 'el.lexemeId' ], 'el')
      ->where_in('el.entryId', $entryIds ?: [ 0 ])
      ->order_by_asc('el.lexemeRank')
      ->find_many();

    foreach ($lexemes as $l) {
      self::$entryLexemes[$l->entryId][$l->main][] = $l;
      unset($l->entryId, $l->main);
    }
  }

  static function getEntryLexemes($entryId) {
    self::loadEntryLexemes([$entryId]);
    return array_merge(
      self::$entryLexemes[$entryId][1],
      self::$entryLexemes[$entryId][0]
    );
  }

  static function getEntryMainLexemes($entryId) {
    self::loadEntryLexemes([$entryId]);
    return self::$entryLexemes[$entryId][1];
  }

  static function getEntryVariants($entryId) {
    self::loadEntryLexemes([$entryId]);
    return self::$entryLexemes[$entryId][0];
  }

  /************************** an object's tags **************************/

  /**
   * Map [objectType][objectId] => list of tags. Object types are defined in
   * ObjectTag::TYPE_*.
   */
  private static array $tags = [];

  /**
   * Loads tags for all meanings with the given IDs.
   */
  static function loadTags(int $objectType, array $ids) {
    $existingIds = self::$tags[$objectType] ?? [];
    $ids = self::filterIds($ids, $existingIds);

    if (empty($ids)) {
      return;
    }

    $loadedTags = Model::factory('Tag')
      ->select('Tag.*')
      ->select('ObjectTag.objectId')
      ->join('ObjectTag', ['Tag.id', '=', 'tagId'])
      ->where('ObjectTag.objectType', $objectType)
      ->where_in('ObjectTag.objectId', $ids ?: [ 0 ])
      ->order_by_asc('ObjectTag.id')
      ->find_many();

    // initialize to empty lists so we don't reload them
    foreach ($ids as $id) {
      self::$tags[$objectType][$id] = [];
    }
    foreach ($loadedTags as $t) {
      self::$tags[$objectType][$t->objectId][] = $t;
      unset($t->objectId);
    }
  }

  static function getTags($objectType, $id) {
    self::loadTags($objectType, [$id]);
    return self::$tags[$objectType][$id];
  }

  /* syntactic sugars */
  static function loadEntryTags($entryIds) {
    self::loadTags(ObjectTag::TYPE_ENTRY, $entryIds);
  }

  static function getEntryTags($entryId) {
    return self::getTags(ObjectTag::TYPE_ENTRY, $entryId);
  }

  static function loadLexemeTags($lexemeIds) {
    self::loadTags(ObjectTag::TYPE_LEXEME, $lexemeIds);
  }

  static function getLexemeTags($lexemeId) {
    return self::getTags(ObjectTag::TYPE_LEXEME, $lexemeId);
  }

  static function loadMeaningTags($meaningIds) {
    self::loadTags(ObjectTag::TYPE_MEANING, $meaningIds);
  }

  static function getMeaningTags($meaningId) {
    return self::getTags(ObjectTag::TYPE_MEANING, $meaningId);
  }

  static function loadTreeTags($treeIds) {
    self::loadTags(ObjectTag::TYPE_TREE, $treeIds);
  }

  static function getTreeTags($treeId) {
    return self::getTags(ObjectTag::TYPE_TREE, $treeId);
  }

  /************************* a meaning's sources *************************/

  /**
   * Maps meaning IDs to lists of sources
   */
  private static array $meaningSources = [];

  /**
   * Loads sources for all meanings with the given IDs.
   */
  static function loadMeaningSources(array $meaningIds) {
    $meaningIds = self::filterIds($meaningIds, self::$meaningSources);

    if (empty($meaningIds)) {
      return;
    }

    $sources = Model::factory('Source')
      ->table_alias('s')
      ->select('s.*')
      ->select('ms.meaningId')
      ->join('MeaningSource', [ 's.id', '=', 'ms.sourceId' ], 'ms')
      ->where_in('ms.meaningId', $meaningIds ?: [ 0 ])
      ->order_by_asc('ms.sourceRank')
      ->find_many();

    foreach ($sources as $s) {
      self::$meaningSources[$s->meaningId][] = $s;
      unset($s->meaningId);
    }
  }

  static function getMeaningSources($meaningId) {
    self::loadMeaningSources([$meaningId]);
    return self::$meaningSources[$meaningId];
  }

  /************************* a meaning's relations *************************/

  /**
   * Maps meaning IDs to lists of *trees*, mapped by relation type.
   */
  private static array $meaningRelations = [];

  /**
   * Loads relations for all meanings with the given IDs.
   */
  static function loadMeaningRelations(array $meaningIds) {
    $meaningIds = self::filterIds($meaningIds, self::$meaningRelations);

    if (empty($meaningIds)) {
      return;
    }

    $trees = Model::factory('Tree')
      ->select('Tree.*')
      ->select('Relation.meaningId')
      ->select('Relation.type')
      ->join('Relation', ['Tree.id', '=', 'treeId'])
      ->where_in('Relation.meaningId', $meaningIds ?: [ 0 ])
      ->order_by_asc('descriptionSort')
      ->find_many();

    foreach ($meaningIds as $meaningId) {
      for ($i = 1; $i <= Relation::NUM_TYPES; $i++) {
        self::$meaningRelations[$meaningId][$i] = [];
      }
    }
    foreach ($trees as $t) {
      self::$meaningRelations[$t->meaningId][$t->type][] = $t;
      unset($t->meaningId, $t->type);
    }
  }

  static function getMeaningRelations($meaningId) {
    self::loadMeaningRelations([$meaningId]);
    return self::$meaningRelations[$meaningId];
  }

  /************************* a tree's meanings *************************/

  /**
   * Maps tree IDs to recursive meaning lists, complete with tags and relations.
   */
  private static array $treeMeanings = [];

  /**
   * Loads relations for all meanings with the given IDs.
   */
  static function loadTreeMeanings(array $treeIds) {
    $treeIds = self::filterIds($treeIds, self::$treeMeanings);

    if (empty($treeIds)) {
      return;
    }

    // initialize to empty trees
    foreach ($treeIds as $treeId) {
      self::$treeMeanings[$treeId] = [];
    }

    $meanings = Model::factory('Meaning')
      ->where_in('treeId', $treeIds ?: [ 0 ])
      ->order_by_asc('displayOrder')
      ->find_many();
    $meaningIds = Util::objectProperty($meanings, 'id');

    // preload related data
    self::loadMeaningRelations($meaningIds);
    self::loadMeaningSources($meaningIds);
    self::loadMeaningTags($meaningIds);
    $mentionMap = Mention::filterMeaningsHavingMentions($meaningIds);

    // build tuples for each meaning
    $tuples = [];
    foreach ($meanings as $m) {
      $tuples[$m->id] = [
        'meaning' => $m,
        'sources' => $m->getSources(),
        'tags' => $m->getTags(),
        'relations' => $m->getRelations(),
        'children' => [],
        // Meaningful for etymologies: the breadcrumb of the lowest ancestor of TYPE_MEANING.
        // Populated by Tree::extractEtymologies().
        'lastBreadcrumb' => null,
        // meanings with incoming mentions cannot be deleted
        'canDelete' => !isset($mentionMap[$m->id]),
      ];
    }

    foreach ($tuples as &$tuple) {
      $m = $tuple['meaning'];
      if ($m->parentId) {
        // move submeaning to its parent's child list
        $tuples[$m->parentId]['children'][] = &$tuple;
      } else {
        // store root meanings in preload map
        self::$treeMeanings[$m->treeId][] = &$tuple;
      }
    }
  }

  static function &getTreeMeanings($treeId) {
    self::loadTreeMeanings([$treeId]);
    return self::$treeMeanings[$treeId];
  }

}
