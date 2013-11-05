<?php

class OpenDataController {

	/*
	Import JSON obtained from maps.ottawa.ca service, discarding any existing data in the destination table.
	*/
	public static function geoOttawaImport($table,$files) {

		if (count($files) == 0) {
			print "ERROR: no files specified\n";
			return;
		}
		foreach ($files as $f) {
			if (!file_exists($f)) {
				print "ERROR: file not found: $f\n";
				return;
			}
		}

		# use field metadata to construct a 'create table' stament.
		$data = json_decode(file_get_contents($files[0]));

		$sql = "  create table $table (\n ";

		#pr($data->fields);

		foreach ($data->fields as $f) {	
			$my_type = "";
			switch ($f->type) {
				case "esriFieldTypeOID":
					$my_type = "mediumint unsigned";
					break;
				case "esriFieldTypeSmallInteger":
				case "esriFieldTypeInteger":
					$my_type = "int";
					break;
				case "esriFieldTypeDouble":
					$my_type = "float";
					break;
				case "esriFieldTypeString":
					$my_type = "varchar({$f->length})";
					break;
				default:
					print "ERROR: unknown gis type {$f->type}\n";
					exit;
			}
			$sql .= "  `{$f->name}` $my_type, \n";
		}
		switch ($data->geometryType) {
			case "esriGeometryPoint":
			case "esriGeometryPolygon":
				# ok
				break;
			default:
				print "ERROR: unknown esriGeometryPoint: {$data->geometryType}\n";
				exit;
		}

		$sql .= "  `shape` geometry \n";
		$sql .= " ) engine = innodb \n";

		# drop current data
		try {
			getDatabase()->execute(" drop table $table ");
		} catch (Exception $e) {
			if (!preg_match('/Unknown table/',$e)) {
				throw($e);
			}
		}

		#print "\n\n$sql\n\n";
		getDatabase()->execute($sql);

		foreach ($files as $f) {
			print "Importing $f: \n";
			$data = json_decode(file_get_contents($f));
			foreach ($data->features as $f) {
				print ".";
				switch ($data->geometryType) {
					case "esriGeometryPoint":
						$p = mercatorToLatLon($f->geometry->x,$f->geometry->y);
						$shapeValue = " PointFromText(' POINT( {$p['lon']} {$p['lat']} ) ') ";
						break;
					case "esriGeometryPolygon":
						$rings = $f->geometry->rings;
						if (count($rings) != 1) {
							print "ERROR: not build to handle multiple-ring-polygons yet\n";
							exit;
						}
						$ring = $rings[0];
						$points = array();
						foreach ($ring as $point) {
							$p = mercatorToLatLon($point[0],$point[1]);
							$points[] = $p;
						}
						$shapeValues = " PolygonFromText( ";
						pr($points);
						exit;
						break;
					default:
						pr($f);
						print "ERROR: unknown esriGeometryPoint: {$data->geometryType}\n";
						exit;
				}
				$id = db_insert($table,get_object_vars($f->attributes));
				getDatabase()->execute(" update $table set shape = $shapeValue where id = $id ");
			}
			print "\n";
		}

	}

  /*
  Scan the data.ottawa.ca website and injest all datasets and the files within the sets.
  */
  public static function scanOpenData() {
	  $datasets = self::getDatasets();

    foreach ($datasets as $d) {

      $set = self::getDataset($d);

      $values = array();
      $values['guid'] = $set->id;
      $values['name'] = $set->name;
      $values['title'] = $set->title;
      $values['url'] = $set->ckan_url;
      $values['updated'] = $set->metadata_modified;
			$id = db_save('opendata',$values,'guid');
      $row = getDatabase()->one(" select * from opendata where guid = :id ",array('id'=>$set->id));

			$dataid = $row['id'];

      foreach ($set->resources as $r) {

        $values = array();
        $values['dataid'] = $dataid;
        $values['description'] = trim($r->description);
        $values['format'] = $r->format;
        $values['guid'] = $r->id;
        $values['hash'] = $r->hash;
        $values['name'] = $r->name;
        $values['size'] = $r->size;
        $values['url'] = $r->url;

	      $row = getDatabase()->one(" select * from opendatafile where guid = :id ",array('id'=>$r->id));
				if ($row['id']) {
					# exists
	        $values['id'] = $row['id'];
					if ($row['hash'] != $r->hash) {
						# changed hash means real update
		        $values['updated'] = $r->last_modified;
					}
					# update meta data, if changed, though it probably hasnt
					db_save('opendatafile',$values,'id');
				} else {
	        db_insert('opendatafile',$values);
				}
      }

    }
  }

	public static function callData($path) {
	  $json = `wget -qO - "http://data.ottawa.ca/$path"`;
	  $result = json_decode($json);
	  return $result;
	}
	
	public static function getDatasets() {
	  $list = self::callData("/api/1/rest/dataset");
	  return $list;
	}
	
	public static function getDataset($name) {
	  $dataset = self::callData("/api/1/rest/dataset/$name");
	  return $dataset;
	}

  /*

  */
  public static function doList() {
    top();

    ?>
    <div class="row-fluid">
    <div class="span3">
    <h1>OpenData</h1>
    </div>
    <div class="span9">
    <p class="lead">
    <b>data.ottawa.ca</b> is Ottawa's open data portal. As data is added/updated
    on the portal it will pop to the top of the below list. For more information about
    each file use the <i>dataset</i> link to learn who maintains the data, and metadata
    about the file itself.
    </p>
    </div>
    </div>
    <?php

    $rows = getDatabase()->all("
      select
        d.title,d.url,
        f.size,f.description,f.format,f.name as fname,f.url as fileurl, left(f.updated,10) updated
      from opendatafile f
        join opendata d on d.id = f.dataid
      order by
        f.updated desc
    ");
    ?>
    <table class="table table-bordered table-hover table-condensed" style="width: 98%;">
    <tr>
    <th>Updated</th>
    <th>File</th>
    <th>Dataset</th>
    <th>Size (kB)</th>
    <th>Format</th>
    <th>Description</th>
    </tr>
    <?php
    foreach ($rows as $r) {
      $size = $r['size'];
      if ($size > 0) {
        $size = intval($size/1024);
      }
      ?>
      <tr>
      <td><nobr><?php print $r['updated']; ?></nobr></td>
      <td><nobr><a href="<?php print $r['fileurl']; ?>"><?php print $r['fname']; ?></a></nobr></td>
      <td><nobr><a href="<?php print $r['url']; ?>"><?php print $r['title']; ?></a></nobr></td>
      <td><?php print $size ?></td>
      <td><?php print $r['format']; ?></td>
      <td><?php print $r['description']; ?></td>
      </tr>
      <?php
    }
    ?>
    </table>
    <?php
    bottom();
  }
	
}


