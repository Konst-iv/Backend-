<?php

namespace App\Controller\Admin;

use App\Entity\Booking;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;

class BookingCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Booking::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Бронирование')
            ->setEntityLabelInPlural('Бронирования')
            ->setSearchFields(['comment', 'status'])
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setPaginatorPageSize(20);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('customer', 'Пользователь'))
            ->add(EntityFilter::new('house', 'Дом'))
            ->add(ChoiceFilter::new('status', 'Статус')
                ->setChoices([
                    'Ожидание' => 'pending',
                    'Подтверждено' => 'confirmed',
                    'Отменено' => 'cancelled',
                    'Завершено' => 'completed'
                ]));
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        
        yield AssociationField::new('customer', 'Пользователь');
        yield AssociationField::new('house', 'Дом');
        yield TextareaField::new('comment', 'Комментарий')->hideOnIndex();
        
        yield DateField::new('checkIn', 'Дата заезда')
            ->setFormat('yyyy-MM-dd')
            ->setFormTypeOptions([
                'widget' => 'single_text',
                'html5' => false,
                'attr' => ['class' => 'datepicker']
            ]);
            
        yield DateField::new('checkOut', 'Дата выезда')
            ->setFormat('yyyy-MM-dd')
            ->setFormTypeOptions([
                'widget' => 'single_text',
                'html5' => false,
                'attr' => ['class' => 'datepicker']
            ]);
        
        yield ChoiceField::new('status', 'Статус')
            ->setChoices([
                'Ожидание' => 'pending',
                'Подтверждено' => 'confirmed',
                'Отменено' => 'cancelled',
                'Завершено' => 'completed'
            ]);
        
        yield DateField::new('createdAt', 'Создано')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->onlyOnIndex();
            
        yield DateField::new('updatedAt', 'Обновлено')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->onlyOnIndex();
    }
}