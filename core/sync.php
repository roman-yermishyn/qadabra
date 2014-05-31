<?php
	function pfsync(&$getter)
	{
	  // $getter - ссылка на объект класса-геттера для получения данных документа, который утверждается
	  // если функция возвращает 0, то считается, что процесс синхронизации успешно завершен
	  
		switch($getter->type)
		{
			case 'new_employee': // приказ о приеме на работу
				
				// ЭТО ПРИМЕР
				// !!!!
				
				// Получаем справочник групп инвалидности
				$groups = $getter->getInvalidGroups();
				foreach($groups as $grp)
				{
					// Обращаемся к полям группы инвалидности
					$grp['id']; // id группы 
					$grp['value']; // название группы 
				}

				// Получаем автора документа
				$author_id = $getter->authorId;
				
				// КОНЕЦ ПРИМЕРА !!!!
				
				
				break;
			case 'change_eu': // приказ о переводе на другую должность
				
				break;
			case 'absent_period_holiday': // Приказ на отпуск
				
				break;
			case 'absent_period_mission':; // Приказ на командировку (общебанковский)
			case 'absent_period_mission2': // Приказ на командировку по подраделениям
				
				break;
			case 'dismiss': // Приказ об увольнении
				
				break;
			case 'staff_list_confirm_by_changes':; // Утверждение штатного с внесенными изменения
			case 'staff_list_protocol_changes':; // Утверждние изменений в ШР соогласно протокола персонального комитета
			case 'staff_list_order_changes':; // Изменения в ШР
			case 'staff_list_auto_order_changes':; // Тоже внесение изменений в ШР...
			case 'salary_matrix_confirm': // утверждение тарифной сетки
				
				break;
				
			default:
				break;
		}
		
		
	  return 0;
	}
?>