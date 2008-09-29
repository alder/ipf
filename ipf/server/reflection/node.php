<?php

class IPF_Server_Reflection_Node
{
    protected $_value = null;
    protected $_children = array();
    protected $_parent = null;

    public function __construct($value, IPF_Server_Reflection_Node $parent = null)
    {
        $this->_value = $value;
        if (null !== $parent) {
            $this->setParent($parent, true);
        }

        return $this;
    }

    public function setParent(IPF_Server_Reflection_Node $node, $new = false)
    {
        $this->_parent = $node;

        if ($new) {
            $node->attachChild($this);
            return;
        }
    }

    public function createChild($value)
    {
        $child = new self($value, $this);

        return $child;
    }

    public function attachChild(IPF_Server_Reflection_Node $node)
    {
        $this->_children[] = $node;

        if ($node->getParent() !== $this) {
            $node->setParent($this);
        }
    }

    public function getChildren()
    {
        return $this->_children;
    }

    public function hasChildren()
    {
        return count($this->_children) > 0;
    }

    public function getParent()
    {
        return $this->_parent;
    }

    public function getValue()
    {
        return $this->_value;
    }

    public function setValue($value)
    {
        $this->_value = $value;
    }

    public function getEndPoints()
    {
        $endPoints = array();
        if (!$this->hasChildren()) {
            return $endPoints;
        }

        foreach ($this->_children as $child) {
            $value = $child->getValue();

            if (null === $value) {
                $endPoints[] = $this;
            } elseif ((null !== $value)
                && $child->hasChildren())
            {
                $childEndPoints = $child->getEndPoints();
                if (!empty($childEndPoints)) {
                    $endPoints = array_merge($endPoints, $childEndPoints);
                }
            } elseif ((null !== $value) && !$child->hasChildren()) {
                $endPoints[] = $child;
            }
        }

        return $endPoints;
    }
}
