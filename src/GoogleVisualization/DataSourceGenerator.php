<?php

namespace GoogleVisualization;

class DataSourceGenerator
{

    /**
     * Creates a Json like string to be passed to Google Visualization
     *
     * This function takes runs the generate function and then parses the result in the Json like format required
     * by Google Visualization.
     *
     * @param $objects
     * @param $columns
     *
     * @return string
     */
    public static function generateJson($objects, $columns)
    {
        return Notation::encode(static::generate($objects, $columns));
    }

    /**
     * Generates a PHP Object with the required structure
     *
     * This function takes an array of objects (datasets) that should be turned into rows.
     * The second parameter contains an associative array which takes the columns and their type.
     * This type should be of according to the DataSource allowed types.
     *
     * @param $objects
     * @param $columns
     *
     * @return DataSource
     */
    public static function generate($objects, $columns)
    {
        if (!is_array($objects)) {
            throw new \InvalidArgumentException('Expected objects array for first parameter, got ' . gettype($objects));
        }
        if (!is_array($columns)) {
            throw new \InvalidArgumentException('Expected columns array for second parameter, got ' . gettype($columns));
        }

        $ds = new DataSource();
        // Create columns
        foreach ($columns as $id => $column) {
            if (is_array($column)) {
                $column['id'] = $id;
                $ds->cols[]   = static::createColumn($column);
            } else {
                $ds->cols[] = static::createColumn(['type' => $column, 'id' => $id]);
            }
        }
        // Create rows
        foreach ($objects as $object) {
            $row = new \stdClass();
            $row->c = static::createCells($object, $ds->cols);
            $ds->rows[] = $row;
        }

        return $ds;
    }

    /**
     * Creates a column definition object
     *
     * @param $columnParameters
     *
     * @return \stdClass
     */
    public static function createColumn($columnParameters)
    {
        if (!is_array($columnParameters)) {
            throw new \InvalidArgumentException('Expected array for first parameter, got ' . gettype($columnParameters));
        }
        $column = new \stdClass();
        foreach ($columnParameters as $key => $value) {
            $column->{$key} = $value;
        }

        return $column;
    }

    /**
     * Creates the data cells for a row from an object
     *
     * @param $object
     * @param $columns
     *
     * @return array
     */
    public static function createCells($object, $columns)
    {
        $cells = [];
        // Iterate over columns array for correct order
        // Use key to extract value from object and create new cell
        $objectArr = (array)$object;
        foreach ($columns as $column) {
            $cell = new \stdClass();

            if (!isset($objectArr[$column->id])) {
                $cell->v = $column->type == 'number' ? 0 : null;
                $cells[] = $cell;
                continue;
            }

            $value = $objectArr[$column->id];

            switch ($column->type) {
                case 'number':
                    if (!is_numeric($value)) {
                        throw new \InvalidArgumentException("A field that was supposed to be interpreted as a number is not numeric");
                    }
                    $cell->v = $value + 0;
                    break;

                case 'datetime':
                case 'date':
                    if ($value instanceof \DateTime) {
                        $cell->v = $value;
                    } elseif (strcasecmp($value, "null") === 0) {
                        $cell->v = null;
                    } else {
                        $cell->v = new \DateTime($value);
                    }
                    break;

                default:
                    $cell->v = $value;
                    break;
            }
            $cells[] = $cell;
        }

        return $cells;
    }
}
