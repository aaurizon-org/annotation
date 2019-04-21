<?php

namespace Kiss;

/**
 * @see https://github.com/phpDocumentor/fig-standards/blob/master/proposed/phpdoc.md
 */
class Annotation
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $data;

    /**
     * @var array
     */
    protected $attributes = [];

    /**
     * Annotation constructor.
     * @param string $name
     * @param string $data
     * @param array $attributes
     */
    public function __construct(string $name, string $data = '', array $attributes = [])
    {
        $this->name = $name;
        $this->data = $data;

        foreach ($attributes as $key => $val)
        {
            $this->attributes[] = $val;
        }
    }

    /**
     * @return string
     */
    public function getName() : string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getData() : string
    {
        return $this->data;
    }

    /**
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * @param int $number
     * @return mixed|null
     */
    public function getAttribute($number = 0)
    {
        return $this->attributes[$number] ?? null;
    }

    /**
     * @param int $number
     * @return bool
     */
    public function hasAttribute($number = 0)
    {
        return (isset($this->attributes[$number]) || array_key_exists($number, $this->attributes)) ? true : false;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->name;
    }

    /**
     * @param string $comment
     * @return static[]
     */
    public static function fromString(string $comment)
    {
        $annotation_list = [];

        foreach (preg_split('/\v+/', $comment) as $comment_line)
        {
            $comment_line = trim($comment_line);
            $comment_line = trim($comment_line, "\t */");
            $comment_line = trim($comment_line);

            if (preg_match('/^@([a-z]+)(\((.*)\))?\h*(.*)/i', $comment_line, $matches))
            {
                $annotation_list[] = $annotation = new static($matches[1], $matches[4]);

                // attributes
                if (preg_match_all('/(([a-z]+)\h*=\h*)?(("(.+)")|(\{(.+)\}))/iU', $matches[3], $matchesParams, PREG_SET_ORDER))
                {
                    foreach ($matchesParams as $attr)
                    {
                        $name = strlen($attr[2]) ? $attr[2] : null;

                        if (strlen($attr[5]))
                        {
                            $name ? $annotation->attributes[$name] = $attr[5] : $annotation->attributes[] = $attr[5];
                        }
                        else
                        {
                            preg_match_all('/"(.*)"/iU', $attr[7], $matchesTab);
                            $name ? $annotation->attributes[$name] = $matchesTab[1] : $annotation->attributes[] = $matchesTab[1];
                        }
                    }
                }
            }
            elseif (isset($annotation))
            {
                $annotation->data.= PHP_EOL.$comment_line;
            }
            else
            {
                // Comment, text, before the first annotation
                // Do nothing
            }
        }

        return $annotation_list;
    }

    /**
     * @param \ReflectionClass $class
     * @return static[]
     */
    public static function fromReflectionClass(\ReflectionClass $class)
    {
        return static::fromString($class->getDocComment());
    }

    /**
     * @param string $class_name
     * @return static[]
     */
    public static function fromClassName(string $class_name)
    {
        try
        {
            $class = new \ReflectionClass($class_name);

            return static::fromReflectionClass($class);
        }
        catch (\ReflectionException $e)
        {
            return [];
        }
    }
}
