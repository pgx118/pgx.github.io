import turtle
while 1:
    shu = int(input("输入你要测试呢内容：\n1.BMI计算\n2.BMI计算\n3.绘图\n4.退出"))
    if shu ==1:

            a=float(input("身高："))
            b=eval(input("体重："))
            c=b/(a ** 2)
            if c < 18.5:
                print(f"您的BMI值为：{c:.2f}属于“偏瘦”")
            elif 18.5 <= c < 24:
                print(f"您的BMI值为：{c:.2f}属于“正常”")
            elif 24 <= c < 28:
                print(f"您的BMI值为：{c:.2f}属于“超重”")
            else:
                print(f"您的BMI值为：{c:.2f}属于“肥胖”")
    elif shu ==2:
        print("其他")
    elif shu == 3:
        turtle.setup(600,600)
        turtle.title("绘图")
        turtle.bgcolor("white")

        turtle.pencolor("yellow")
        turtle.pensize(2)

        turtle.speed(10) #速度
        turtle.fillcolor("red")

        turtle.color("red")

        turtle.end_fill()
        for i in range(36):
            turtle.fd(200)

            # turtle.setheading(90)
            turtle.right(170)
            # turtle.left(90)

        # turtle.end_fill()

        turtle.hideturtle()
        turtle.done()
    elif shu == 4:
        break
