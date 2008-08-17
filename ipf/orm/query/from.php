<?php

class IPF_ORM_Query_From extends IPF_ORM_Query_Part
{
    public function parse($str, $return = false)
    {
        $str = trim($str);
        $parts = $this->_tokenizer->bracketExplode($str, 'JOIN');

        $from = $return ? array() : null;

        $operator = false;

        switch (trim($parts[0])) {
            case 'INNER':
                $operator = ':';
            case 'LEFT':
                array_shift($parts);
            break;
        }

        $last = '';

        foreach ($parts as $k => $part) {
            $part = trim($part);

            if (empty($part)) {
                continue;
            }

            $e = explode(' ', $part);

            if (end($e) == 'INNER' || end($e) == 'LEFT') {
                $last = array_pop($e);
            }
            $part = implode(' ', $e);

            foreach ($this->_tokenizer->bracketExplode($part, ',') as $reference) {
                $reference = trim($reference);
                $e = explode(' ', $reference);
                $e2 = explode('.', $e[0]);

                if ($operator) {
                    $e[0] = array_shift($e2) . $operator . implode('.', $e2);
                }

                if ($return) {
                    $from[] = $e;
                } else {
                    $table = $this->query->load(implode(' ', $e));
                }
            }

            $operator = ($last == 'INNER') ? ':' : '.';
        }
        return $from;
    }
}
