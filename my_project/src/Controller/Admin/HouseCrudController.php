<?php
namespace App\Controller\Admin;

use App\Entity\House;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;

class HouseCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return House::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Дом')
            ->setEntityLabelInPlural('Дома')
            ->setSearchFields(['name', 'amenities'])
            ->setDefaultSort(['id' => 'DESC'])
            ->setPaginatorPageSize(20);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('name', 'Название'))
            ->add(NumericFilter::new('beds', 'Количество спальных мест'))
            ->add(NumericFilter::new('distanceToSea', 'Расстояние до моря (м)'))
            ->add(BooleanFilter::new('isAvailable', 'Доступен'))
            ->add(NumericFilter::new('pricePerNight', 'Цена за ночь'));
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('name', 'Название');
        yield IntegerField::new('beds', 'Спальные места');
        yield TextareaField::new('amenities', 'Удобства')
            ->hideOnIndex();
        yield IntegerField::new('distanceToSea', 'Расстояние до моря (м)');
        yield MoneyField::new('pricePerNight', 'Цена за ночь')
            ->setCurrency('RUB')
            ->setStoredAsCents(false);
        yield BooleanField::new('isAvailable', 'Доступен');
    }
}