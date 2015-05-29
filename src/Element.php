<?php

namespace HTMLToMarkdown;

class Element implements ElementInterface
{
    /**
     * @var \DOMNode
     */
    protected $node;

    /**
     * @var ElementInterface|null
     */
    protected $nextCached;

    public function __construct(\DOMNode $node)
    {
        $this->node = $node;
    }

    /**
     * @return bool
     */
    public function isBlock()
    {
        switch ($this->getTagName()) {
            case 'blockquote':
            case 'body':
            case 'code':
            case 'h1':
            case 'h2':
            case 'h3':
            case 'h4':
            case 'h5':
            case 'h6':
            case 'hr':
            case 'html':
            case 'li':
            case 'p':
            case 'ol':
            case 'ul':
                return true;
            default:
                return false;
        }
    }

    /**
     * @return bool
     */
    public function isText()
    {
        return $this->getTagName() === '#text';
    }

    /**
     * @return bool
     */
    public function isWhitespace()
    {
        return $this->getTagName() === '#text' && trim($this->getValue()) === '';
    }

    /**
     * @return string
     */
    public function getTagName()
    {
        return $this->node->nodeName;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->node->nodeValue;
    }

    /**
     * @return ElementInterface|null
     */
    public function getParent()
    {
        return new static($this->node->parentNode) ?: null;
    }

    /**
     * @return bool
     */
    public function hasChildren()
    {
        return $this->node->hasChildNodes();
    }

    /**
     * @return ElementInterface[]
     */
    public function getChildren()
    {
        return array_map(function (\DOMNode $node) {
            return new static($node);
        }, iterator_to_array($this->node->childNodes));
    }

    /**
     * @return ElementInterface|null
     */
    public function getNext()
    {
        if ($this->nextCached === null) {
            $nextNode = $this->getNextNode($this->node);
            if ($nextNode !== null) {
                $this->nextCached = new static($nextNode);
            }
        }

        return $this->nextCached;
    }

    /**
     * @param \DomNode $node
     *
     * @return \DomNode|null
     */
    private function getNextNode($node, $checkChildren = true)
    {
        if ($checkChildren && $node->firstChild) {
            return $node->firstChild;
        } elseif ($node->nextSibling) {
            return $node->nextSibling;
        } elseif ($node->parentNode) {
            return $this->getNextNode($node->parentNode, false);
        } else {
            return null;
        }
    }

    /**
     * @param string[]|string $tagNames
     *
     * @return bool
     */
    public function isDescendantOf($tagNames)
    {
        if (!is_array($tagNames)) {
            $tagNames = array($tagNames);
        }

        for ($p = $this->node->parentNode; $p != false; $p = $p->parentNode) {
            if (is_null($p)) {
                return false;
            }

            if (in_array($p->nodeName, $tagNames)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $markdown
     */
    public function setFinalMarkdown($markdown)
    {
        $markdown_node = $this->node->ownerDocument->createTextNode($markdown);
        $this->node->parentNode->replaceChild($markdown_node, $this->node);
    }

    /**
     * @return string
     */
    public function getChildrenAsString()
    {
        return $this->node->C14N();
    }

    /**
     * @return int
     */
    public function getSiblingPosition()
    {
        $position = 0;

        // Loop through all nodes and find the given $node
        foreach ($this->getParent()->getChildren() as $current_node) {
            if (!$current_node->isWhitespace()) {
                $position++;
            }

            // TODO: Need a less-buggy way of comparing these
            // Perhaps we can somehow ensure that we always have the exact same object and use === instead?
            // Or maybe implement an ->equals() method
            if ($current_node == $this) {
                break;
            }
        }

        return $position;
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public function getAttribute($name)
    {
        if ($this->node instanceof \DOMElement) {
            return $this->node->getAttribute($name);
        }

        return '';
    }
}
