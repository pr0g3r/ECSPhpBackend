# Generated by Django 3.2.7 on 2021-11-24 12:23

from django.db import migrations, models


class Migration(migrations.Migration):

    dependencies = [
        ('order', '0012_auto_20211124_1210'),
    ]

    operations = [
        migrations.AddField(
            model_name='returnitems',
            name='price',
            field=models.FloatField(default=2.5),
            preserve_default=False,
        ),
        migrations.AddField(
            model_name='returnitems',
            name='shipping',
            field=models.FloatField(default=2.0),
            preserve_default=False,
        ),
    ]
