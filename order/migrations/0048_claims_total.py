# Generated by Django 3.2.7 on 2022-04-01 07:50

from django.db import migrations, models


class Migration(migrations.Migration):

    dependencies = [
        ('order', '0047_remove_order_date'),
    ]

    operations = [
        migrations.AddField(
            model_name='claims',
            name='total',
            field=models.FloatField(default=0),
            preserve_default=False,
        ),
    ]
