<?php

class Eclat
{
    private int $minSupport = 2;
    private float $minConfidence = 0.3;
    private array $sets, $rules;
    public function __construct(int $minSupport = 2, float $minConfidence=0.3)
    {
        $this->minSupport = $minSupport;
        $this->minConfidence = $minConfidence;
        $this->sets = [];
        $this->rules = [];
    }

    public function train($samples) : void
    {
        $this->eclat($samples);
        $this->calculateRules($samples);
    }

    /**
     * Eclat php implemented algorithm. It is half written by Bing chat, it can be somewhat wrong
     * @param array $transactions samples to train
     * @return void
     */
    function eclat(array $transactions): void
    {
        $this->sets = [];
        $singletons = [];
        foreach ($transactions as $t)
        {
            foreach ($t as $item)
            {
                if (!isset($singletons[$item]))
                {
                    $singletons[$item] =['support' => 1, 'tids' => [$t]];
                }
                else
                {
                    $singletons[$item]['support'] += 1;
                    $singletons[$item]['tids'][] = $t;
                }
            }
        }
        foreach ($singletons as $item => $data)
        {
            if ($data['support'] < $this->minSupport) continue;
            $this->sets[implode(',', [$item])] =
                [
                    'itemset' => [$item],
                    'support' => $data['support']
                ];
            $this->eclatRecursion([$item], $data['tids'], $this->minSupport, $this->sets);
        }
        $this->sets = array_values($this->sets);
    }

    function eclatRecursion($prefix, $prefix_tids, $minSupport, &$frequentSets): void
    {
        $itemsets = [];
        foreach ($prefix_tids as $ptid)
        {
            foreach ($ptid as $item)
            {
                if (in_array($item, $prefix)) continue;
                if (!isset($itemsets[$item]))
                {
                    $itemsets[$item] = ['support' => 1, 'tids' => [$ptid]];
                }
                else
                {
                    $itemsets[$item]['support'] += 1;
                    $itemsets[$item]['tids'][] = $ptid;
                }
            }
        }
        foreach ($itemsets as $item => $data)
        {
            if ($data['support'] < $minSupport) continue;
            $new_prefix = array_merge($prefix, [$item]);
            sort($new_prefix);
            $frequentSets[implode(',', $new_prefix)] =
                [
                    'itemset' => $new_prefix,
                    'support' => $data['support']
                ];
            $this->eclatRecursion($new_prefix, $data['tids'], $minSupport, $frequentSets);
        }
    }

    /**
     * Calculate confidence for got rules
     * @param array $transactions samples to train
     * @return void
     */
    function calculateRules(array $transactions): void
    {
        $this->rules = [];
        foreach ($this->sets as $itemset)
        {
            $set = $itemset['itemset'];
            $count = $itemset['support'];

            if (count($set) <= 1) continue;
            for ($i = 0; $i < count($set); $i++)
            {
                $antecedent = array_slice($set, 0, $i);
                $consequent = array_slice($set, $i);

                if (!(count($antecedent) > 0 && count($consequent) > 0)) continue;

                $support_antecedent = $this->getSetSupport($transactions, $antecedent);
                $support_itemset = $this->getSetSupport($transactions, $set);

                $confidence = $support_itemset / $support_antecedent;
                if($confidence < $this->minConfidence) continue;
                $rule = [
                    'antecedent' => $antecedent,
                    'consequent' => $consequent,
                    'support' => $count,
                    'confidence' => $confidence,
                ];
                $this->rules[]= $rule;
            }
        }
    }

    function getSetSupport($transactions, $set) : int
    {
        $count = 0;
        foreach ($transactions as $transaction)
        {
            if (!$this->array_contains($transaction, $set)) continue;
            $count++;
        }
        return $count;
    }

    function array_contains($haystack, $needle) : bool
    {
        return count(array_intersect($haystack, $needle)) == count($needle);
    }


    public function getFrequency(): array
    {
        return $this->sets;
    }

    public function getRules(): array
    {
        return $this->rules;
    }
}
