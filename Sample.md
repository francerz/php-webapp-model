# Código ideal de consulta

```php
$grupo_id = [1, 3, 7];
$rows = Grupos::getRows(
    ['grupo_id' => $grupo_id],
    [
        'grupo_id', 'departamento', 'asignatura',
        'Horarios',
        'Estudiantes' => ['semestre' => 8]
    ]
);
```

```php
class Grupo extends AbstractModel
{
    protected function modelBuilder()
    {
        $query->addInnerJoin(Docente::class)
            ->column(['docente' => 'nombre'])
            ->on('docente_id', '=', '@docente_id');
        
        $query->addInnerJoin(Asignatura::class)
            ->column(['asignatura' => 'nombre'])
            ->on('asignatura_id', '=', 'asignatura_id');

        $query->addNestCollection('Horarios', $nestQuery, $row, Horario::class)
            ->where('hg.grupo_id', $row->grupo_id)
            ->andNull('hg.delete_time');
        
        $query->addNestCollection('Estudiante', $nestQuery, $row, Estudiante::class)
            ->where('ge.grupo_id', $row->grupo_id);
        
        $query->nestCollection('Periodo', $nestQuery, $row, Periodo::class)
            ->where('p.periodo_id', $row->periodo_id);
    }
}
```

```php
$query = Query::selectFrom('grupos AS g');
$query->innerJoin('docentes AS d', ['docente' => 'nombre'])
    ->on('d.docente_id', 'g.docente_id');
$query->innerJoin('departamentos AS dp', ['departamento' => 'nombre'])
    ->on('dp.departamento_id', 'd.departamento_id');
$query->innerJoin('asignaturas AS a')->on('a.id_asignatura', 'g.asignatura_id');
$query->columns([
    'grupo_id',
    'docente' => 'd.nombre',
    'departamento' => 'dp.nombre',
    'asignatura' => 'a.nombre'
]);
$query->where('g.id_grupo', $grupo_id);

$query = Query::selectFrom($query, ['grupo_id', 'asignatura']);

$nestQuery = Horario::getQuery($params->getSubparams('Horarios'));
$query->nest(['Horarios' => $nestQuery], function (NestedSelect $nest, RowProxy $row) {
    $nest->getSelect()->where('hg.grupo_id', $row->grupo_id);
}, NestMode::COLLECTION, Horario::class);

$nestQuery = Estudiante::getQuery($params->getSubparams('Estudiantes'));
$query->nest(['Estudiantes' => function() {
        
}], function (NestedSelect $nest, RowProxy $row) {
    $nest->getSelect()->where('ge.grupo_id', $row->grupo_id);
}, NestMode::COLLECTION, Estudiante::class);
```
